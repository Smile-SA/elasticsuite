<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Console;

use Magento\Framework\Console\Cli;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker as DataChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fix invalid behavioral data console command.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class FixData extends Command
{
    /**
     * @var DataChecker
     */
    private $checker;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @param DataChecker           $checker      Data checker.
     * @param StoreManagerInterface $storeManager Store manager.
     * @param string|null           $name         The name of the command; passing null means it must be set in configure().
     *
     * @throws LogicException When the command name is empty
     */
    public function __construct(DataChecker $checker, StoreManagerInterface $storeManager, ?string $name = null)
    {
        parent::__construct($name);
        $this->checker = $checker;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('elasticsuite:tracker:fix-data');
        $this->setDescription('Check and fix invalid data in indexed tracker data.');
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progressIndicator = new ProgressIndicator($output, 'verbose', 500, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Processing...');

        $table = new Table($output);
        $table->setHeaders(['Store Name', 'Fixed issues (if any)']);
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $progressIndicator->advance();
            if (!$store->getIsActive()) {
                continue;
            }
            $issuesInfo = '<info>Nothing to fix.</info>';
            $issues = $this->checker->checkAndFixData((int) $store->getId(), $progressIndicator, $output);
            if (!empty($issues)) {
                $issuesInfo = sprintf("<info>%s</info>", join("\n", $issues));
            }
            $table->addRow([$store->getName(), $issuesInfo]);
        }
        $progressIndicator->finish('Done');

        $table->render();

        return Cli::RETURN_SUCCESS;
    }
}
