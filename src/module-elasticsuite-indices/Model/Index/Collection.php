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
namespace Smile\ElasticsuiteIndices\Model\Index;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteIndices\Model\ResourceModel\Index\CollectionFactory as IndexFactory;

/**
 * Class Model: Index Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * @var IndexFactory
     */
    protected $collectionFactory;

    /**
     * @param EntityFactoryInterface $entityFactory     EntityFactory
     * @param IndexFactory           $collectionFactory CollectionFactory
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        IndexFactory $collectionFactory
    ) {
        parent::__construct($entityFactory);

        $this->collectionFactory = $collectionFactory;

        $this->setItemObjectClass(DataObject::class);
    }

    /**
     * @inheritdoc
     *
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log query.
     *
     * @return DataCollection
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function loadData($printQuery = false, $logQuery = false): DataCollection
    {
        $data = [];
        /** @var IndexFactory $collection */
        $collection = $this->collectionFactory->create();
        $indexers = $collection->getItems();
        foreach ($indexers as $index) {
            $data[$index['index_name']] = $index;
        }
        $this->_items = $data;

        return $this;
    }
}
