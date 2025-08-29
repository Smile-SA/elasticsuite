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

use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\CategoryProduct;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * CLI command to migrate legacy product positions in categories
 * to ElasticSuite-compatible ones (positive, contiguous positions).
 *
 * Features:
 * - Interactive migration for a single category (--category option).
 * - Migration for the whole catalog (no option).
 * - Handles negative, zero, positive positions separately.
 * - Resolves conflicts (products with the same position) by reorder or delete.
 * - Supports --dry-run mode (preview changes without persisting).
 * - Runs updates in DB transaction for safety.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CategoryPositionMigrate extends Command
{
    /**
     * CLI option name for specifying a single category ID.
     *
     * Used with:
     * php bin/magento elasticsuite:category-position:migrate --category [id]
     */
    private const OPTION_CATEGORY = 'category';

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
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var CategoryProduct
     */
    private $categoryProduct;

    /**
     * @var CategoryListInterface
     */
    private $categoryList;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var State
     */
    private $appState;

    /**
     * Constructor.
     *
     * @param CategoryRepository    $categoryRepository Category repository for loading categories.
     * @param CategoryProduct       $categoryProduct    Resource model for category-product relation.
     * @param CategoryListInterface $categoryList       Service to fetch category lists.
     * @param SearchCriteriaBuilder $criteriaBuilder    Builder for search criteria in repository queries.
     * @param State                 $appState           Application state (for setting area code).
     * @param string|null           $name               Command name (optional).
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        CategoryProduct $categoryProduct,
        CategoryListInterface $categoryList,
        SearchCriteriaBuilder $criteriaBuilder,
        State $appState,
        string $name = null
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryProduct = $categoryProduct;
        $this->categoryList = $categoryList;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->appState = $appState;

        parent::__construct($name);
    }

    /**
     * Configure CLI command options and description.
     */
    protected function configure(): void
    {
        $this->setName('elasticsuite:category-position:migrate')
            ->setDescription('Migrate legacy product positions in categories to ElasticSuite-compatible ones.')
            ->addOption(
                self::OPTION_CATEGORY,
                null,
                InputOption::VALUE_REQUIRED,
                'Category ID (if omitted, process whole catalog)'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Preview migration without updating the database'
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input  CLI input.
     * @param OutputInterface $output CLI output.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Ensure area code is set safely.
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // Area code is already set, safe to continue.
        }

        $categoryId = $input->getOption(self::OPTION_CATEGORY);
        $dryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);

        if ($categoryId) {
            $output->writeln("<info>Processing category ID {$categoryId}</info>");
            try {
                $this->categoryRepository->get((int) $categoryId);
                $this->processCategory(
                    (int) $categoryId,
                    $output,
                    $input,
                    null,
                    null,
                    null,
                    null,
                    false,
                    $dryRun
                );
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
                return Cli::RETURN_FAILURE;
            }
        } else {
            $output->writeln("<info>Processing all categories...</info>");

            // Ask migration rules once for the whole catalog.
            [$migrateNegative, $migrateZero, $migratePositive, $conflictStrategy] = $this->askMigrationRules($input, $output);

            $criteria = $this->criteriaBuilder->create();
            $categories = $this->categoryList->getList($criteria)->getItems();

            foreach ($categories as $category) {
                if ((int) $category->getId() === 1) {
                    continue; // skip root category.
                }
                $output->writeln("<comment>Processing category ID {$category->getId()} ({$category->getName()})</comment>");

                $this->processCategory(
                    (int) $category->getId(),
                    $output,
                    $input,
                    $migrateNegative,
                    $migrateZero,
                    $migratePositive,
                    $conflictStrategy,
                    true,
                    $dryRun
                );
            }
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * Process migration for a single category.
     *
     * @param int             $categoryId      Category ID.
     * @param OutputInterface $output          CLI output.
     * @param InputInterface  $input           CLI input.
     * @param bool|null       $migrateNegative Handle negative positions (null = ask interactively).
     * @param bool|null       $migrateZero     Handle zero positions (null = ask interactively).
     * @param bool|null       $migratePositive Handle positive positions (null = ask interactively).
     * @param string|null     $conflictStrategy Conflict strategy (reorder|delete) (null = ask interactively).
     * @param bool            $silentMode      If true, limit preview (for catalog mode).
     * @param bool            $dryRun          If true, preview only (no DB updates).
     */
    private function processCategory(
        int $categoryId,
        OutputInterface $output,
        InputInterface $input,
        ?bool $migrateNegative = null,
        ?bool $migrateZero = null,
        ?bool $migratePositive = null,
        ?string $conflictStrategy = null,
        bool $silentMode = false,
        bool $dryRun = false
    ): void {
        $connection = $this->categoryProduct->getConnection();
        $table = $this->categoryProduct->getMainTable();

        $select = $connection->select()
            ->from($table, ['product_id', 'position'])
            ->where('category_id = ?', $categoryId)
            ->order('position ASC');

        $rows = $connection->fetchAll($select);

        if (!$rows) {
            $output->writeln("<comment>No products found for category {$categoryId}</comment>");
            return;
        }

        if (!$silentMode) {
            $output->writeln("<info>Found " . count($rows) . " products</info>");
            $output->writeln("| product_id | position |");
            $output->writeln("|------------|----------|");
            foreach (array_slice($rows, 0, 100) as $row) {
                $output->writeln(sprintf("| %10s | %8s |", $row['product_id'], $row['position']));
            }
            if (count($rows) > 100) {
                $output->writeln("... and more");
            }
        }

        // If rules not given, ask interactively.
        if ($migrateNegative === null || $migrateZero === null || $migratePositive === null || $conflictStrategy === null) {
            [$migrateNegative, $migrateZero, $migratePositive, $conflictStrategy] = $this->askMigrationRules($input, $output);
        }

        // --- Apply migration ---
        $updates = [];
        $toDelete = [];

        // Step 1: filter products by rules.
        foreach ($rows as $row) {
            $position = (int) $row['position'];
            $pid = (int) $row['product_id'];

            if ($position < 0 && !$migrateNegative) {
                continue;
            }
            if ($position === 0 && !$migrateZero) {
                continue;
            }
            if ($position > 0 && !$migratePositive) {
                continue;
            }

            $updates[] = [
                'product_id' => $pid,
                'position'   => $position,
            ];
        }

        // Step 2: reorder positions sequentially.
        usort($updates, fn($a, $b) => $a['position'] <=> $b['position']);

        $normalized = [];
        $newPos = 1;
        foreach ($updates as $update) {
            $pid = $update['product_id'];
            if (isset($normalized[$update['position']])) {
                // Conflict.
                if ($conflictStrategy === 'delete') {
                    $toDelete[] = $pid;
                    continue;
                }
            }
            $normalized[$newPos] = $pid;
            $newPos++;
        }

        // Step 3: persist or preview.
        if ($dryRun) {
            $output->writeln("<comment>[Dry-Run]</comment> Category {$categoryId}: would migrate " . count($normalized) . " products.");
            if (!empty($toDelete)) {
                $output->writeln("<comment>[Dry-Run]</comment> Would delete " . count($toDelete) . " products due to conflicts.");
            }
            return;
        }

        $connection->beginTransaction();
        try {
            if (!empty($toDelete)) {
                $connection->delete(
                    $table,
                    ['category_id = ?' => $categoryId, 'product_id IN (?)' => $toDelete]
                );
                $output->writeln("<comment>Deleted " . count($toDelete) . " products due to conflicts.</comment>");
            }

            $i = 1;
            foreach ($normalized as $pos => $pid) {
                $connection->update(
                    $table,
                    ['position' => $i],
                    ['category_id = ?' => $categoryId, 'product_id = ?' => $pid]
                );
                $i++;
            }

            $connection->commit();
            $output->writeln("<info>Category {$categoryId}: migrated " . count($normalized) . " products.</info>");
        } catch (\Exception $e) {
            $connection->rollBack();
            $output->writeln("<error>Migration failed for category {$categoryId}: {$e->getMessage()}</error>");
        }
    }

    /**
     * Ask the user migration rules (interactive mode).
     *
     * @param InputInterface  $input  CLI input.
     * @param OutputInterface $output CLI output.
     *
     * @return array{bool,bool,bool,string} [migrateNegative, migrateZero, migratePositive, conflictStrategy]
     */
    private function askMigrationRules(InputInterface $input, OutputInterface $output): array
    {
        $helper = $this->getHelper('question');

        $migrateNegative = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer negative positions to contiguous positive ones? (y/n) ', false)
        );

        $migrateZero = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer zero positions? (y/n) ', false)
        );

        $migratePositive = $helper->ask(
            $input,
            $output,
            new ConfirmationQuestion('Transfer positive positions? (y/n) ', true)
        );

        $conflictQuestion = new ChoiceQuestion(
            'What to do with products sharing the same position?',
            ['reorder', 'delete'],
            0
        );
        $conflictStrategy = $helper->ask($input, $output, $conflictQuestion);

        return [$migrateNegative, $migrateZero, $migratePositive, $conflictStrategy];
    }
}
