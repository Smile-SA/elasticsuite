<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer;

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Optimizer Limitation Resource Model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Limitation extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Retrieve applicable optimizer Ids for a given category Id.
     *
     * @param int $categoryId The category Id
     *
     * @return array
     */
    public function getApplicableOptimizerIdsByCategoryId($categoryId)
    {
        return $this->getApplicationData('catalog_view_container', 'category_id', $categoryId);
    }

    /**
     * Retrieve applicable optimizer Ids for a given query Id.
     *
     * @param int    $queryId       The query Id
     * @param string $containerName The container name if needed.
     *
     * @return array
     */
    public function getApplicableOptimizerIdsByQueryId($queryId, $containerName = 'quick_search_container')
    {
        return $this->getApplicationData($containerName, 'query_id', $queryId);
    }

    /**
     * Retrieve all categories associated to a given optimizer.
     *
     * @param OptimizerInterface $optimizer The Optimizer
     *
     * @return array
     */
    public function getCategoryIdsByOptimizer(OptimizerInterface $optimizer)
    {
        return $this->getLimitationData($optimizer, 'category_id');
    }

    /**
     * Retrieve all search queries associated to a given optimizer.
     *
     * @param OptimizerInterface $optimizer The optimizer
     *
     * @return array
     */
    public function getQueryIdsByOptimizer(OptimizerInterface $optimizer)
    {
        return $this->getLimitationData($optimizer, 'query_id');
    }

    /**
     * Save limitation data for a given optimizer.
     *
     * @param OptimizerInterface $optimizer      The optimizer.
     * @param array              $limitationData An array containing limitation data to save.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveLimitation($optimizer, $limitationData)
    {
        $rows        = [];
        $optimizerId = (int) $optimizer->getId();

        $this->getConnection()->delete(
            $this->getMainTable(),
            $this->getConnection()->quoteInto(OptimizerInterface::OPTIMIZER_ID . " = ?", $optimizerId)
        );

        $fields = $this->getConnection()->describeTable($this->getMainTable());
        foreach ($limitationData as $item) {
            $item[$this->getIdFieldName()] = $optimizerId;
            $rows[] = array_replace(array_fill_keys(array_keys($fields), null), array_intersect_key($item, $fields));
        }

        $result = true;
        if (!empty($rows)) {
            $result = (bool) $this->getConnection()->insertArray($this->getMainTable(), array_keys($fields), $rows);
        }

        return $result;
    }

    /**
     * Retrieve applicable optimizer ids for a given entity_id (could be a category_id or query_id).
     *
     * @param string $container The search container to filter on (quick_search_container or catalog_view_container).
     * @param string $column    The column to filter on (category_id or query_id).
     * @param int    $idValue   The id of entity to filter
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getApplicationData($container, $column, $idValue)
    {
        $select = $this->getConnection()
            ->select()
            ->from(['o' => $this->getTable(OptimizerInterface::TABLE_NAME)], [OptimizerInterface::OPTIMIZER_ID])
            ->joinInner(
                ['osc' => $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER)],
                "o.optimizer_id = osc.optimizer_id",
                []
            )
            ->joinLeft(
                ['main_table' => $this->getMainTable()],
                "o.optimizer_id = main_table.optimizer_id",
                []
            )
            ->where($this->getConnection()->quoteInto("osc.search_container = ?", (string) $container))
            ->group(OptimizerInterface::OPTIMIZER_ID);

        $limitationConditions = [
            $this->getConnection()->quoteInto(
                "osc.apply_to = 0 OR (osc.apply_to = 1 AND main_table.{$column} = ?)",
                (int) $idValue
            ),
        ];
        $exclusionCondition = "osc.apply_to = 2";
        $excluded = $this->getExclusionData($container, $column, $idValue);
        if (!empty($excluded)) {
            $exclusionCondition = $this->getConnection()->quoteInto(
                "(osc.apply_to = 2 AND main_table.optimizer_id NOT IN (?))",
                $excluded,
                null,
                count($excluded)
            );
        }
        $limitationConditions[] = $exclusionCondition;
        $select->where(implode(' OR ', $limitationConditions));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init(OptimizerInterface::TABLE_NAME_LIMITATION, OptimizerInterface::OPTIMIZER_ID);
    }

    /**
     * Get Limitation data for a given optimizer.
     *
     * @param OptimizerInterface $optimizer The optimizer
     * @param string             $column    The column to fetch
     *
     * @return array
     */
    private function getLimitationData(OptimizerInterface $optimizer, $column)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from($this->getMainTable(), $column)
                       ->where($this->getConnection()->quoteInto(OptimizerInterface::OPTIMIZER_ID . " = ?", (int) $optimizer->getId()));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Retrieve non-applicable optimizer ids for a given entity_id (could be a category_id or query_id).
     *
     * @param string $container The search container to filter on (quick_search_container or catalog_view_container).
     * @param string $column    The column to filter on (category_id or query_id).
     * @param int    $idValue   The id of entity to filter
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getExclusionData($container, $column, $idValue)
    {
        $select = $this->getConnection()
            ->select()
            ->from(['o' => $this->getTable(OptimizerInterface::TABLE_NAME)], [OptimizerInterface::OPTIMIZER_ID])
            ->joinInner(
                ['osc' => $this->getTable(OptimizerInterface::TABLE_NAME_SEARCH_CONTAINER)],
                "o.optimizer_id = osc.optimizer_id",
                []
            )
            ->joinLeft(
                ['main_table' => $this->getMainTable()],
                "o.optimizer_id = main_table.optimizer_id",
                []
            )
            ->where($this->getConnection()->quoteInto("osc.search_container = ?", (string) $container))
            ->where(
                $this->getConnection()->quoteInto(
                    "(osc.apply_to = 2 AND main_table.{$column} = ?)",
                    (int) $idValue
                )
            )
            ->group(OptimizerInterface::OPTIMIZER_ID);

        return $this->getConnection()->fetchCol($select);
    }
}
