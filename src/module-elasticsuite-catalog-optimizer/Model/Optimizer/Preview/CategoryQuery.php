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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;

use Magento\Catalog\Api\Data\CategoryInterface;
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
     * @var \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider
     */
    private $categoryFilterProvider;

    /**
     * CategoryQuery constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\Builder      $queryBuilder           Query Builder
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory           Query Factory
     * @param \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider $categoryFilterProvider Category Filter Provider
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalog\Model\Category\Filter\Provider $categoryFilterProvider
    ) {
        $this->queryBuilder           = $queryBuilder;
        $this->queryFactory           = $queryFactory;
        $this->categoryFilterProvider = $categoryFilterProvider;
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
        $filters = array_merge(
            ['category' => $this->categoryFilterProvider->getQueryFilter($category)],
            $containerConfigurationInterface->getFilters()
        );

        $filterQuery  = $this->queryBuilder->createFilterQuery($containerConfigurationInterface, $filters);

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
}
