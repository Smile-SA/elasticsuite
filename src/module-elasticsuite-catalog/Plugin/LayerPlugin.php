<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin;

use Magento\CatalogInventory\Model\Plugin\Layer;

/**
 * Replace is in stock native filter on layer.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class LayerPlugin extends \Magento\CatalogInventory\Model\Plugin\Layer
{
    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * Catalog config
     *
     * @var \Magento\Catalog\Model\Config
     */
    private $catalogConfig;

    /**
     * @var \Smile\ElasticsuiteCore\Helper\Mapping
     */
    private $mappingHelper;

    /**
     * Constructor.
     *
     * @param \Magento\CatalogInventory\Helper\Stock             $stockHelper   Stock helper.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig   Configuration.
     * @param \Magento\Search\Model\QueryFactory                 $queryFactory  Search query factory.
     * @param \Magento\Catalog\Model\Config                      $catalogConfig Catalog Configuration.
     * @param \Smile\ElasticsuiteCore\Helper\Mapping             $mappingHelper Mapping Helper.
     */
    public function __construct(
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper
    ) {
        parent::__construct($stockHelper, $scopeConfig);
        $this->queryFactory  = $queryFactory;
        $this->catalogConfig = $catalogConfig;
        $this->mappingHelper = $mappingHelper;
    }


    /**
     * {@inheritDoc}
     */
    public function beforePrepareProductCollection(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        if ($this->_isEnabledShowOutOfStock() === false) {
            $collection->addIsInStockFilter();
        }

        $this->setSortParams($layer, $collection);
    }

    /**
     * Apply sort params to the collection.
     *
     * @param \Magento\Catalog\Model\Layer                                       $layer      Catalog / search layer.
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection Product collection.
     *
     * @return $this
     */
    private function setSortParams(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        $searchQuery = $this->queryFactory->get();

        if (!$searchQuery->getQueryText() && $layer->getCurrentCategory()) {
            $categoryId = $layer->getCurrentCategory()->getId();
            $collection->addSortFilterParameters('position', 'category.position', 'category', ['category.category_id' => $categoryId]);
        }

        foreach ($this->catalogConfig->getAttributesUsedForSortBy() as $attributeCode => $attribute) {
            if ($attribute->usesSource()) {
                $sortField = $this->mappingHelper->getOptionTextFieldName($attributeCode);
                $collection->addSortFilterParameters($attributeCode, $sortField);
            }
        }

        return $this;
    }
}
