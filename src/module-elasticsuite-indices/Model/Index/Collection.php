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
     * {@inheritdoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (in_array($field, ['index_alias', 'index_name'])) {
            if (is_array($condition)) {
                foreach ($condition as $value) {
                    $this->addFilter($field, preg_replace('/[^A-Za-z0-9\-_]/', '', $value->__toString()));
                }
            }
        }

        return $this;
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
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $filters = $this->getFilter([]);
        $indices = $collection->getItems();
        if (!empty($filters)) {
            $indices = $this->applyPostFilters($filters, $indices);
        }

        foreach ($indices as $index) {
            $data[$index['index_name']] = $index;
        }
        $this->_items = $data;

        return $this;
    }

    /**
     * Apply post filters to a loaded index collection items.
     *
     * @param array $filters Array of filters.
     * @param array $indices Array of indices.
     *
     * @return array
     */
    private function applyPostFilters(array $filters = [], array $indices = [])
    {
        $filtered = [];

        foreach ($indices as $index) {
            $keep = true;
            foreach ($filters as $filter) {
                $column = $filter->getField();
                $value = $filter->getValue();
                if (strpos((string) $index->getData($column), $value) === false) {
                    $keep = false;
                    break;
                }
            }
            if ($keep) {
                $filtered[] = $index;
            }
        }

        return $filtered;
    }
}
