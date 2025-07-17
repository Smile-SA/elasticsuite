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

use DateTime;
use DateTimeInterface;
use Exception;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Indexer\IndexerInterface as IndexerModel;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer\Collection as IndexerCollection;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteIndices\Helper\Settings;

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
     * @var Settings
     */
    protected $helper;

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
     * @param Settings                 $helper            Settings helper.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        IndexerCollectionFactory $collectionFactory,
        IndexSettings $indexSettings,
        Settings $helper
    ) {
        parent::__construct($entityFactory);

        $this->collectionFactory = $collectionFactory;
        $this->indexSettings = $indexSettings;
        $this->helper = $helper;
    }

    /**
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log Query.
     * @return Collection
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @throws Exception
     */
    public function loadData($printQuery = false, $logQuery = false): Collection
    {
        /** @var IndexerCollection $collection */
        $collection = $this->collectionFactory->create();
        $indexers = $collection->getItems();
        $data = [];
        $indicesMapping = $this->helper->getMapping();
        foreach ($indexers as $indexer) {
            /** @var IndexerModel $indexer */
            if ($indexer->getStatus() === StateInterface::STATUS_WORKING) {
                $item = $this->prepareItem($indexer);
                if (array_key_exists($item['indexer_id'], $indicesMapping)) {
                    $indexUpdateDate = DateTime::createFromFormat(
                        \Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT,
                        $item['indexer_updated']
                    );
                    $indexNameSuffix = $this->indexSettings->getIndexNameSuffix($indexUpdateDate);

                    foreach ($indicesMapping[$item['indexer_id']] as $index) {
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
