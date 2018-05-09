<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Category Query Builder for Optimizer Preview
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryQuery
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CategoryQuery constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\Builder      $queryBuilder Query Builder
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory Query Factory
     * @param ScopeConfigInterface                                      $scopeConfig  Scope Configuration
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->queryFactory = $queryFactory;
        $this->scopeConfig  = $scopeConfig;
    }

    /**
     * Retrieve Search Query for a category
     *
     * @param ContainerConfigurationInterface $containerConfigurationInterface Container Configuration
     * @param CategoryInterface               $category                        Category
     *
     * @return QueryInterface
     */
    public function getCategorySearchQuery(
        ContainerConfigurationInterface $containerConfigurationInterface,
        CategoryInterface $category
    ) {
        $filterParams = [
            'category.category_id' => $category->getId(),
            'visibility'           => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH],
        ];

        if (!$this->isEnabledShowOutOfStock($category->getStoreId())) {
            $filterParams['stock.is_in_stock'] = true;
        }

        if ($category->getVirtualRule()) { // Implicit dependency to Virtual Categories module.
            $filterParams['category'] = $category->getVirtualRule()->getCategorySearchQuery($category);
            unset($filterParams['category.category_id']);
        }

        $filterQuery = $this->queryBuilder->createFilterQuery($containerConfigurationInterface, $filterParams);

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, ['filter' => $filterQuery]);
    }

    /**
     * Retrieve Category Sort Orders
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category The Category
     *
     * @return array
     */
    public function getCategorySortOrders(CategoryInterface $category)
    {
        return [
            'category.position' => [
                'direction'    => 'asc',
                'sortField'    => 'category.position',
                'nestedPath'   => 'category',
                'nestedFilter' => ['category.category_id' => $category->getId()],
            ],
        ];
    }

    /**
     * Get config value for 'display out of stock' option
     *
     * @param int $storeId The Store Id
     *
     * @return bool
     */
    private function isEnabledShowOutOfStock($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            'cataloginventory/options/show_out_of_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
