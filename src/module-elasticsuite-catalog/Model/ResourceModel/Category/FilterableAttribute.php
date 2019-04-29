<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterDisplayMode;

/**
 * Resource Model For Category Layered Navigation Filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FilterableAttribute extends AbstractDb
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * FilterableAttributeList constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context        Context
     * @param \Magento\Framework\EntityManager\MetadataPool     $metadataPool   Metadata Pool
     * @param null                                              $connectionName Connection Name
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        $this->metadataPool = $metadataPool;
        parent::__construct($context, $connectionName);
    }

    /**
     * Retrieve Layered Navigation Filters for a given category.
     *
     * @param int $categoryId The category Id
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadAttributesData(int $categoryId)
    {
        $binds = [':category_id' => $categoryId];

        $select = $this->getConnection()->select();
        $select->from($this->getMainTable())->where(sprintf("%s = :category_id", $this->getIdFieldName()));

        return $this->getConnection()->fetchAll($select, $binds);
    }

    /**
     * Save Layered Navigation Filters configuration for a given category.
     *
     * @param int   $categoryId The category Id
     * @param array $data       layered navigation filters configuration data.
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveAttributesData(int $categoryId, array $data)
    {
        $rows = [];
        $this->deleteByCategoryId($categoryId);

        $fields = $this->getConnection()->describeTable($this->getMainTable());
        foreach ($data as $item) {
            $item[$this->getIdFieldName()] = $categoryId;
            $row                           = $this->buildRow($item, $fields);
            if ($this->isValid($row)) {
                $rows[] = $row;
            }
        }

        $result = true;
        if (!empty($rows)) {
            $result = (bool) $this->getConnection()->insertArray($this->getMainTable(), array_keys($fields), $rows);
        }

        return $result;
    }

    /**
     * Delete layered filters configuration by Category Id.
     *
     * @param int $categoryId Category Id
     *
     * @return bool
     */
    public function deleteByCategoryId(int $categoryId)
    {
        $deleteCondition = [sprintf("%s = ?", $this->getIdFieldName()) => $categoryId];

        return (bool) $this->getConnection()->delete($this->getMainTable(), $deleteCondition);
    }

    /**
     * Resource initialization
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName) Method is inherited.
     *
     * @return void
     */
    protected function _construct()
    {
        $idField = $this->metadataPool->getMetadata(CategoryInterface::class)->getIdentifierField();
        $this->_init('smile_elasticsuitecatalog_category_filterable_attribute', $idField);
    }

    /**
     * Compute row data for later insert.
     *
     * @param array $item   The item to build
     * @param array $fields The fields to keep
     *
     * @return array
     */
    private function buildRow($item, $fields)
    {
        // Remove position for not pinned items.
        if (empty($item['is_pinned']) || filter_var($item['is_pinned'], FILTER_VALIDATE_BOOLEAN) === false) {
            $item['position'] = null;
        }

        foreach (array_keys($fields) as $field) {
            $useDefault = 'use_default_' . $field;
            if (isset($item[$useDefault]) && (filter_var($item[$useDefault], FILTER_VALIDATE_BOOLEAN) === true)) {
                $item[$field] = null;
            }
        }

        // Remove non-existent columns and sort the other according to fields order.
        return array_replace(array_flip(array_keys($fields)), array_intersect_key($item, $fields));
    }

    /**
     * Check if a given row is relevant to save.
     *
     * @param array $row A row to be saved in table.
     *
     * @return boolean
     */
    private function isValid($row)
    {
        $result = false;

        // Check if facet_display_mode is defaulted.
        if ((int) $row['facet_display_mode'] !== FilterDisplayMode::AUTO_DISPLAYED) {
            $result = true;
        }

        // Check remaining fields are not null.
        unset($row[$this->getIdFieldName()]);
        unset($row['attribute_id']);
        unset($row['facet_display_mode']);

        if (!empty(array_filter($row, 'strlen'))) { // Preserve 0 values.
            $result = true;
        }

        return $result;
    }
}
