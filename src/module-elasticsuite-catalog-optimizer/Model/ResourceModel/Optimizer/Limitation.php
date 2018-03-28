<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
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
            OptimizerInterface::TABLE_NAME_LIMITATION,
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
            ->from(OptimizerInterface::TABLE_NAME_LIMITATION, $column)
            ->where($this->getConnection()->quoteInto(OptimizerInterface::OPTIMIZER_ID . " = ?", (int) $optimizer->getId()));

        return $this->getConnection()->fetchCol($select);
    }
}
