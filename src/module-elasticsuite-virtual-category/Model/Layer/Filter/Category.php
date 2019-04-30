<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter;

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
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider
     */
    private $filterProvider;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                   $filterItemFactory   Filter item factory.
     * @param \Magento\Store\Model\StoreManagerInterface                        $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                      $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder              $itemDataBuilder     Item data builder.
     * @param \Magento\Framework\Escaper                                        $escaper             HTML escaper.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory  $dataProviderFactory Data provider.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                $scopeConfig         Scope config.
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface               $context             Search context.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider      Category Filter provider.
     * @param boolean                                                           $useUrlRewrites      Uses URLs rewrite for rendering.
     * @param array                                                             $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $dataProviderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $context,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider,
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
            $scopeConfig,
            $context,
            $useUrlRewrites,
            $data
        );

        $this->filterProvider = $filterProvider;
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
        $query = $this->getFilterQuery();

        if ($query !== null) {
            $this->getLayer()->getProductCollection()->addQueryFilter($query);
        }

        return $this;
    }

    /**
     * Current category filter query.
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getFilterQuery()
    {
        return $this->filterProvider->getQueryFilter($this->getDataProvider()->getCategory());
    }
}
