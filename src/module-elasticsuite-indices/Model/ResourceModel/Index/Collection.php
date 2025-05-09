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
namespace Smile\ElasticsuiteIndices\Model\ResourceModel\Index;

use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * Class Resource Model: Index Collection
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Collection extends DataCollection
{
    /**
     * @var IndexStatsProvider
     */
    protected $indexStatsProvider;

    /**
     * @param EntityFactoryInterface $entityFactory      Entity factory.
     * @param IndexStatsProvider     $indexStatsProvider Index stats provider.
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        IndexStatsProvider $indexStatsProvider
    ) {
        parent::__construct($entityFactory);

        $this->setItemObjectClass(DataObject::class);
        $this->indexStatsProvider = $indexStatsProvider;
    }

    /**
     * Search all items by field value
     *
     * @param string $column Column.
     * @param mixed  $value  Value.
     * @return array
     */
    public function getItemsByColumnValue($column, $value)
    {
        $this->load();

        $res = [];
        foreach ($this as $item) {
            if (strpos((string) $item->getData($column), $value) !== false) {
                $res[] = $item;
            }
        }

        return $res;
    }

    /**
     * @inheritdoc
     *
     * @param bool $printQuery Is print query.
     * @param bool $logQuery   Is log query.
     *
     * @return Collection IndicesCollection.
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false): Collection
    {
        foreach ($this->indexStatsProvider->getElasticSuiteIndices() as $indexName => $alias) {
            $item = $this->getNewEmptyItem();

            $this->addItem($item->setData($this->indexStatsProvider->indexStats($indexName, $alias)));
        }
        $this->_setIsLoaded(true);

        return $this;
    }
}
