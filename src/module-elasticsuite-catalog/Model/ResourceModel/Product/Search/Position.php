<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search;

use Magento\Search\Model\Query;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Product search position resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Position extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var string
     */
    const TABLE_NAME = 'smile_elasticsuitecatalog_search_query_product_position';

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context         Context.
     * @param \Magento\Framework\Indexer\IndexerRegistry        $indexerRegistry Indexer registry.
     * @param string                                            $connectionName  Connection name.
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        $connectionName = null
    ) {
        $this->indexerRegistry = $indexerRegistry;
        parent::__construct($context, $connectionName);
    }

    /**
     * Get query position for a product list.
     *
     * @param array $productIds Product ids.
     * @param int   $storeId    Store ids.
     *
     * @return array
     */
    public function getByProductIds(array $productIds, $storeId)
    {
        $searchQueryTable = $this->getTable('search_query');
        $select = $this->getBaseSelect()
            ->joinInner($searchQueryTable, "main_table.query_id = {$searchQueryTable}.query_id", [])
            ->where('product_id IN(?)', $productIds)
            ->where('store_id = ?', $storeId)
            ->columns(['product_id', 'query_id', 'position']);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Load product positions for the given category.
     *
     * @param Query|int $query Query.
     *
     * @return array
     */
    public function getProductPositionsByQuery($query)
    {
        if (is_object($query)) {
            $query = $query->getId();
        }

        $select = $this->getBaseSelect()
            ->where('query_id = ?', (int) $query)
            ->columns(['product_id', 'position'])
            ->order('position');

        return $this->getConnection()->fetchPairs($select);
    }

    /**
     * Save the product positions.
     *
     * @param int   $queryId             Query id.
     * @param array $newProductPositions Product positions.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position
     */
    public function saveProductPositions($queryId, $newProductPositions)
    {
        $reindexedProductIds = array_merge(
            array_keys($newProductPositions),
            array_keys($this->getProductPositionsByQuery($queryId))
        );

        $deleteConditions = [
            $this->getConnection()->quoteInto('query_id = ?', (int) $queryId),
        ];

        if (!empty($newProductPositions)) {
            $insertData = [];

            foreach ($newProductPositions as $productId => $position) {
                $insertData[] = [
                    'query_id' => $queryId,
                    'product_id'  => $productId,
                    'position'    => $position,
                ];
            }

            $deleteConditions[] = $this->getConnection()->quoteInto(
                'product_id NOT IN (?)',
                array_keys($newProductPositions)
            );
            $this->getConnection()->insertOnDuplicate($this->getMainTable(), $insertData, array_keys(current($insertData)));
        }

        $this->getConnection()->delete($this->getMainTable(), implode(' AND ', $deleteConditions));
        $this->reindex(array_unique($reindexedProductIds));

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_setMainTable(self::TABLE_NAME);
    }

    /**
     * Reindex product on position change.
     *
     * @param array $productIds Product ids to be reindexed.
     *
     * @return void
     */
    private function reindex($productIds)
    {
        $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID)->reindexList($productIds);
    }

    /**
     * Init a base select with the main table.
     *
     * @return \Zend_Db_Select
     */
    private function getBaseSelect()
    {
        $select = $this->getConnection()->select();
        $select->from(['main_table' => $this->getMainTable()], []);

        return $select;
    }
}
