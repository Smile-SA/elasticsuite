<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Export;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection;
use Magento\ImportExport\Model\Export\AbstractEntity;
use Magento\ImportExport\Model\Export\Factory as ExportFactory;
use Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\Import\CategoryAttribute as CategoryAttributeImport;

/**
 * Category attribute export model.
 *
 * Exports the same columns the elasticsuite_category_attribute import entity expects
 * (see Model/Import/CategoryAttribute.php), one row per catalog_category-scoped EAV attribute,
 * so an exported file can be edited and re-imported as-is.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 */
class CategoryAttribute extends AbstractEntity
{
    /**
     * Entity type code.
     */
    const ENTITY_TYPE_CODE = 'elasticsuite_category_attribute';

    /**
     * Backend type used for the synthetic column attributes exposed to the admin Filter grid.
     *
     * @var array
     */
    private $columnBackendTypes = [
        'attribute_code'            => 'varchar',
        'attribute_label'           => 'varchar',
        'is_searchable'             => 'int',
        'search_weight'             => 'int',
        'is_used_in_spellcheck'     => 'int',
        'used_for_sort_by'          => 'int',
        'default_analyzer'          => 'varchar',
        'norms_disabled'            => 'int',
        'is_spannable'              => 'int',
        'include_zero_false_values' => 'int',
    ];

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var AttributeFactory
     */
    private $attributeFactory;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var CategoryAttributeImport
     */
    private $categoryAttributeImport;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface              $scopeConfig                Scope Config.
     * @param StoreManagerInterface             $storeManager               Store Manager.
     * @param ExportFactory                     $collectionFactory          Export Collection Factory.
     * @param CollectionByPagesIteratorFactory  $resourceColFactory         Collection By Pages Iterator Factory.
     * @param EavConfig                         $eavConfig                  EAV Config.
     * @param AttributeFactory                  $attributeFactory           Attribute Factory.
     * @param AttributeCollectionFactory        $attributeCollectionFactory Attribute Collection Factory.
     * @param CategoryAttributeImport           $categoryAttributeImport    Category Attribute Import Model.
     * @param array                             $data                       Additional Data.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ExportFactory $collectionFactory,
        CollectionByPagesIteratorFactory $resourceColFactory,
        EavConfig $eavConfig,
        AttributeFactory $attributeFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        CategoryAttributeImport $categoryAttributeImport,
        array $data = []
    ) {
        $this->eavConfig                  = $eavConfig;
        $this->attributeFactory           = $attributeFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->categoryAttributeImport    = $categoryAttributeImport;

        parent::__construct($scopeConfig, $storeManager, $collectionFactory, $resourceColFactory, $data);
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return self::ENTITY_TYPE_CODE;
    }

    /**
     * Synthetic column collection, used only to render the admin Export screen's Filter grid.
     *
     * These are not real catalog_eav_attribute rows: they represent the fixed set of columns this
     * entity always exports, one per Model\Import\CategoryAttribute::getValidColumnNames() entry.
     * Ticking "skip" on any of them in the Filter grid has no effect on export() below, since the
     * import side requires every column to be present on every row.
     *
     * @return Collection
     */
    public function getAttributeCollection()
    {
        if (count($this->_attributeCollection) === 0) {
            foreach ($this->categoryAttributeImport->getValidColumnNames() as $columnName) {
                $attribute = $this->attributeFactory->create();
                $attribute->setId($columnName);
                $attribute->setAttributeCode($columnName);
                $attribute->setDefaultFrontendLabel($columnName);
                $attribute->setBackendType($this->columnBackendTypes[$columnName] ?? 'varchar');
                $this->_attributeCollection->addItem($attribute);
            }
        }

        return $this->_attributeCollection;
    }

    /**
     * Header columns, always the full set (see getAttributeCollection() docblock).
     *
     * @return array
     */
    protected function _getHeaderColumns()
    {
        return $this->categoryAttributeImport->getValidColumnNames();
    }

    /**
     * Not used: export() below iterates the real category attribute collection directly.
     *
     * @return null
     */
    protected function _getEntityCollection()
    {
        return null;
    }

    /**
     * Not used: export() below iterates the real category attribute collection directly.
     *
     * @param \Magento\Framework\Model\AbstractModel $item Item.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function exportItem($item)
    {
    }

    /**
     * Export process.
     *
     * @return string
     */
    public function export()
    {
        $writer = $this->getWriter();
        $writer->setHeaderCols($this->_getHeaderColumns());

        $entityType = $this->eavConfig->getEntityType(Category::ENTITY);

        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->setEntityTypeFilter($entityType);

        foreach ($attributeCollection as $attribute) {
            $row = [];
            foreach ($this->_getHeaderColumns() as $columnName) {
                $row[$columnName] = $columnName === 'attribute_label'
                    ? $attribute->getData('frontend_label')
                    : $attribute->getData($columnName);
            }
            $writer->writeRow($row);
        }

        return $writer->getContents();
    }
}
