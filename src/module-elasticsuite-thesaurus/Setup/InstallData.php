<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Setup;

use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Thesaurus Data Installer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * Class Constructor
     *
     * @param IndexerInterfaceFactory $indexerFactory Indexer Factory.
     */
    public function __construct(IndexerInterfaceFactory $indexerFactory)
    {
        $this->indexerFactory  = $indexerFactory;
    }

    /**
     * Installs module data.
     * Rebuild Thesaurus index.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param ModuleDataSetupInterface $setup   Setup Interface
     * @param ModuleContextInterface   $context Module Context Interface
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->getIndexer('elasticsuite_thesaurus')->reindexAll();
    }

    /**
     * Retrieve an indexer by its Id
     *
     * @param string $indexerId The indexer Id
     *
     * @return \Magento\Framework\Indexer\IndexerInterface
     */
    private function getIndexer($indexerId)
    {
        return $this->indexerFactory->create()->load($indexerId);
    }
}
