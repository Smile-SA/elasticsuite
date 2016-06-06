<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Product category filter implementation using virtual categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Category extends \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Category
{
    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                  $filterItemFactory   Filter item factory.
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                     $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder             $itemDataBuilder     Item data builder.
     * @param \Magento\Framework\Escaper                                       $escaper             HTML escaper.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory Data provider.
     * @param boolean                                                          $useUrlRewrites      Uses URLs rewrite for rendering.
     * @param array                                                            $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory,
        $useUrlRewrites = false,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $dataProviderFactory,
            $useUrlRewrites,
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection()
    {
        $currentCategory = $this->getDataProvider()->getCategory();
        $facetQueries    = $currentCategory->getVirtualRule()->getSearchQueriesByChildren($currentCategory);

        $facetType   = BucketInterface::TYPE_QUERY_GROUP;
        $facetField  = $this->getFilterField();
        $facetConfig = ['name' => $facetField, 'queries' => $facetQueries];

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, $facetConfig);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilterField()
    {
        return 'categories';
    }

    /**
     * {@inheritDoc}
     */
    protected function applyCategoryFilterToCollection(\Magento\Catalog\Api\Data\CategoryInterface $category)
    {
        $query = $category->getVirtualRule()->getCategorySearchQuery($category);
        $this->getLayer()->getProductCollection()->addQueryFilter($query);

        return $this;
    }
}
