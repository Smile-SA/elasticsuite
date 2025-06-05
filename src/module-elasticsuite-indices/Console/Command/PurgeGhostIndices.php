<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Console\Command;

use Exception;
use Magento\Framework\Console\Cli;
use Smile\ElasticsuiteIndices\Model\GhostIndexPurger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to purge ghost indices.
 *
 * This command uses the GhostIndexPurger service to remove all indices detected as "ghost".
 * It is intended for use when the number of ghost indices is large, which could cause timeouts in the Admin UI.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class PurgeGhostIndices extends Command
{
    /**
     * @var GhostIndexPurger
     */
    private GhostIndexPurger $ghostIndexPurger;

    /**
     * Constructor.
     *
     * @param GhostIndexPurger $ghostIndexPurger Ghost index purging service.
     */
    public function __construct(GhostIndexPurger $ghostIndexPurger)
    {
        parent::__construct();
        $this->ghostIndexPurger = $ghostIndexPurger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('elasticsuite:ghostindices:purge');
        $this->setDescription('Purge all ghost indices.');
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $count = $this->ghostIndexPurger->purge();
            $output->writeln(sprintf('<info>%s ghost indices were deleted.</info>', $count));
        } catch (Exception $e) {
            $output->writeln('<error>An error occurred while purging ghost indices.</error>');

            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
