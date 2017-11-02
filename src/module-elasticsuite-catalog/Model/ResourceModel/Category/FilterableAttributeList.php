<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource Model For Category Layered Navigation Filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FilterableAttributeList extends AbstractDb
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

        foreach ($data as $item) {
            $rows[] = [$categoryId, $item['attribute_id'], $item['position'], $item['display_mode']];
        }

        $cols = [$this->getIdFieldName(), 'attribute_id', 'position', 'display_mode'];
        $result = true;
        if (!empty($rows)) {
            $result = (bool) $this->getConnection()->insertArray($this->getMainTable(), $cols, $rows);
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
}
