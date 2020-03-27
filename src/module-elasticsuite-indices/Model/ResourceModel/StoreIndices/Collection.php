<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Model\ResourceModel\StoreIndices;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Index\IndexSettings;

/**
 * Class Resource Model: Store Indices Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * @var StoreManagerInterface[]
     */
    protected $storeList;

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var IndexSettings
     */
    private $indexSettings;

    /**
     * @param EntityFactoryInterface $entityFactory       EntityFactory.
     * @param StoreManagerInterface  $storeManager        Store Manager.
     * @param IndexSettingsHelper    $indexSettingsHelper ElasticSuite index settings helper.
     * @param IndexSettings          $indexSettings       Index settings.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        StoreManagerInterface $storeManager,
        IndexSettingsHelper $indexSettingsHelper,
        IndexSettings $indexSettings
    ) {
        parent::__construct($entityFactory);
        $this->storeList = $storeManager->getStores();
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexSettings = $indexSettings;
    }

    /**
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log Query.
     * @return Collection
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false): Collection
    {
        $data = [];
        foreach ($this->storeList as $store) {
            foreach (array_keys($this->indexSettings->getIndicesConfig()) as $indexName) {
                $item = $this->prepareItem($store, $indexName);
                $data[] = $item;
            }
        }
        $this->_items = $data;

        return $this;
    }

    /**
     * Prepare a index item.
     *
     * @param StoreInterface $store     Store interface.
     * @param string         $indexName Index name.
     * @return DataObject
     */
    protected function prepareItem(StoreInterface $store, $indexName): DataObject
    {
        $item = new DataObject();
        $item->setData('pattern', $this->indexSettingsHelper->getIndexAliasFromIdentifier($indexName, $store));

        return $item;
    }
}
