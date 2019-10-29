<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin;

/**
 * Prepare collection sort orders.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class LayerPlugin
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
     * @param \Magento\Search\Model\QueryFactory     $queryFactory  Search query factory.
     * @param \Magento\Catalog\Model\Config          $catalogConfig Catalog Configuration.
     * @param \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper Mapping Helper.
     */
    public function __construct(
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper
    ) {
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
            $sortFilter = ['category.category_id' => $categoryId];
            $collection->addSortFilterParameters('position', 'category.position', 'category', $sortFilter);
        } elseif ($searchQuery->getId()) {
            $sortFilter = ['search_query.query_id' => $searchQuery->getId()];
            $collection->addSortFilterParameters('relevance', 'search_query.position', 'search_query', $sortFilter);
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
