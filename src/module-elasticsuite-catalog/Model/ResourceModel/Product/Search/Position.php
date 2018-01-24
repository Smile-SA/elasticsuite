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
     * Save the product postions.
     *
     * @param Query $query Query.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position
     */
    public function saveProductPositions($queryId, $newProductPositions)
    {
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
