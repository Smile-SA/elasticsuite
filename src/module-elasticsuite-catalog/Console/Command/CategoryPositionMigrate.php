<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Smile\ElasticsuiteCatalog\Model\CategoryPositionMigrator;

/**
 * CLI command to migrate legacy product positions in categories
 * to ElasticSuite-compatible ones (positive, contiguous positions).
 *
 * Features:
 * - Interactive migration for a single category (--category [id] option).
 * - Migration for the whole catalog (no option).
 * - Handles negative, zero, positive positions separately.
 * - Resolves conflicts (products with the same position) by reorder or delete.
 * - Supports --dry-run mode (preview changes without persisting).
 * - Runs updates in DB transaction for safety.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryPositionMigrate extends Command
{
    /**
     * CLI option name for specifying a single category ID.
     *
     * Used with:
     * php bin/magento elasticsuite:category-position:migrate --category [id]
     */
    private const OPTION_CATEGORY_ID = 'category';

    /**
     * CLI option name for enabling dry-run mode.
     * If specified, the command will preview migration changes without updating the database.
     *
     * Used with:
     * php bin/magento elasticsuite:category-position:migrate --dry-run
     *
     * Examples:
     * php bin/magento elasticsuite:category-position:migrate --category 45 --dry-run
     *
     * php bin/magento elasticsuite:category-position:migrate --dry-run
     */
    private const OPTION_DRY_RUN = 'dry-run';

    /**
     * @var CategoryPositionMigrator
     */
    private CategoryPositionMigrator $migrator;

    /**
     * @var State
     */
    private $appState;

    /**
     * Constructor.
     *
     * @param CategoryPositionMigrator $migrator Migration service class.
     * @param State                    $appState Application state (for setting area code).
     * @param string|null              $name     Command name (optional).
     */
    public function __construct(
        CategoryPositionMigrator $migrator,
        State $appState,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->migrator = $migrator;
        $this->appState = $appState;
    }

    /**
     * Configure CLI command options and description.
     */
    protected function configure(): void
    {
        $this->setName('elasticsuite:category-position:migrate')
            ->setDescription('Migrate category product positions to ElasticSuite table.')
            ->addOption(
                self::OPTION_CATEGORY_ID,
                null,
                InputArgument::OPTIONAL,
                'Category ID to migrate (omit to process all categories).'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Dry-run: preview only (no database changes will be made).'
            );

        parent::configure();
    }

    /**
     * Execute CLI command.
     *
     * @param InputInterface  $input  The input interface from the CLI context.
     * @param OutputInterface $output The output interface used to write messages to the console.
     *
     * @return int
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Ensure adminhtml area code for resource/model usage.
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Area already set, ignore.
        }

        $categoryId = $input->getOption(self::OPTION_CATEGORY_ID);
        $dryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);
        $helper = $this->getHelper('question');

        // Ask interactive migration options once for all categories.
        $options = $this->askMigrationOptions($helper, $input, $output);

        if ($categoryId === null) {
            // Whole-catalog mode when no categoryId provided.
            $result = $this->migrator->wholeCatalogMigration($options, $dryRun, $output);
        } else {
            // Single category mode.
            $result = $this->migrator->singleCategoryMigration((int) $categoryId, $options, $dryRun, $output);
        }

        // Render preview or summary.
        if ($dryRun && !empty($result['preview'])) {
            $output->writeln('<comment>Preview mode: showing up to 100 rows.</comment>');
            $this->renderTable($output, $result['preview_headers'], $result['preview']);
        }

        $output->writeln(sprintf(
            '<info>Migration finished. Total categories: %d | Migrated: %d</info>',
            $result['categories'] ?? 0,
            $result['migrated'] ?? 0
        ));

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Ask interactive migration options from the CLI user.
     *
     * This method is responsible for collecting interactive answers about how
     * category product positions should be migrated. It presents a series of
     * confirmation and choice questions to the user to determine:
     *
     * - Whether to migrate products with negative positions.
     * - Whether to migrate products with zero positions.
     * - Whether to migrate products with positive positions.
     * - How to handle conflicts (either by reordering or deleting).
     *
     * @param HelperInterface $helper The console helper responsible for handling interactive questions.
     * @param InputInterface  $input  The input interface from the CLI context.
     * @param OutputInterface $output The output interface used to write messages to the console.
     *
     * @return array{
     *     migrateNegative: bool,
     *     migrateZero: bool,
     *     migratePositive: bool,
     *     conflictStrategy: string
     * } An associative array containing the migration options:
     *     - 'migrateNegative': Whether to migrate negative positions.
     *     - 'migrateZero': Whether to migrate zero positions.
     *     - 'migratePositive': Whether to migrate positive positions.
     *     - 'conflictStrategy': Conflict handling mode ('reorder' or 'delete').
     */
    private function askMigrationOptions(HelperInterface $helper, InputInterface $input, OutputInterface $output): array
    {
        $migrateNegative = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer negative positions to positive and contiguous ones (y/n) ', false)
        );

        $migrateZero = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer zero positions? (y/n) ', true)
        );

        $migratePositive = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer positive positions? (y/n) ', true)
        );

        $conflict = $helper->ask(
            $input,
            $output,
            new ChoiceQuestion('What to do with products sharing the same position?', ['reorder', 'delete'], 0)
        );

        return [
            'migrateNegative' => (bool) $migrateNegative,
            'migrateZero' => (bool) $migrateZero,
            'migratePositive' => (bool) $migratePositive,
            'conflictStrategy' => $conflict,
        ];
    }

    /**
     * Render a simple table (Symfony Table) with given headers and rows.
     *
     * @param OutputInterface $output  Console output.
     * @param string[]        $headers Array of column headers for the table.
     * @param array[]         $rows    Array of row data.
     *
     * @return void
     */
    private function renderTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
    }
}
