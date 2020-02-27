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
namespace Smile\ElasticsuiteIndices\Model\ResourceModel\WorkingIndexer;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Indexer\IndexerInterface as IndexerModel;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer\Collection as IndexerCollection;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Zend_Date;

/**
 * Class Resource Model: Indexer Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * Mapping values for indices.
     *
     * @var array
     */
    private $indicesMapping = [
        'catalog_category_product' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalog_product_attribute' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalog_product_category' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalog_product_price' => [
            'catalog_category',
            'catalog_product',
        ],
        'cataloginventory_stock' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalogrule_product' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalogrule_rule' => [
            'catalog_category',
            'catalog_product',
        ],
        'catalogsearch_fulltext' => [
            'catalog_category',
            'catalog_product',
        ],
        'elasticsuite_categories_fulltext' => [
            'catalog_category',
            'catalog_product',
        ],
        'elasticsuite_thesaurus' => [
            'thesaurus',
        ],
        'inventory' => [
            'catalog_category',
            'catalog_product',
        ],
        'salesrule_rule' => [
            'catalog_category',
            'catalog_product',
        ],
    ];

    /**
     * @var IndexerCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var IndexSettings
     */
    private $indexSettings;

    /**
     * @param EntityFactoryInterface   $entityFactory     Entity factory.
     * @param IndexerCollectionFactory $collectionFactory Indexer collection.
     * @param IndexSettings            $indexSettings     ElasticSuite index settings.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        IndexerCollectionFactory $collectionFactory,
        IndexSettings $indexSettings
    ) {
        parent::__construct($entityFactory);

        $this->collectionFactory = $collectionFactory;
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
        /** @var IndexerCollection $collection */
        $collection = $this->collectionFactory->create();
        $indexers = $collection->getItems();
        $data = [];
        foreach ($indexers as $indexer) {
            /** @var IndexerModel $indexer */
            if ($indexer->getStatus() === StateInterface::STATUS_WORKING) {
                $item = $this->prepareItem($indexer);
                if (array_key_exists($item['indexer_id'], $this->indicesMapping)) {
                    $indexUpdateDate = new Zend_Date($item['indexer_updated'], Zend_Date::ISO_8601);
                    $indexNameSuffix = $this->indexSettings->getIndexNameSuffix($indexUpdateDate);

                    foreach ($this->indicesMapping[$item['indexer_id']] as $index) {
                        $data[$index . '_' . $indexNameSuffix] = $item;
                    }
                }
            }
        }
        $this->_items = $data;

        return $this;
    }

    /**
     * Prepare a indexer item.
     *
     * @param IndexerModel $indexer IndexerModel.
     * @return DataObject
     */
    protected function prepareItem(IndexerModel $indexer): DataObject
    {
        $indexerState = $indexer->getState();

        $item = new DataObject();
        $item->setData('indexer_id', $indexer->getId());
        $item->setData('indexer_status', $indexerState->getStatus());
        $item->setData('indexer_updated', $indexerState->getUpdated());

        return $item;
    }
}
