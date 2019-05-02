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
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\RequestFactory;

/**
 * Custom Request Builder used when calculating optimizer preview.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RequestBuilder
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\Builder
     */
    private $queryBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Search\RequestFactory
     */
    private $requestFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var CategoryQuery
     */
    private $categoryQueryBuilder;

    /**
     * @var SearchQuery
     */
    private $searchQueryBuilder;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\SortOrder\SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * RequestBuilder constructor.
     *
     * @param RequestFactory   $requestFactory   Request Factory
     * @param QueryBuilder     $queryBuilder     Query Builder
     * @param QueryFactory     $queryFactory     Query Factory
     * @param CategoryQuery    $categoryQuery    Category Query
     * @param SearchQuery      $searchQuery      Search Query
     * @param SortOrderBuilder $sortOrderBuilder Sort Order Builder
     */
    public function __construct(
        RequestFactory $requestFactory,
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        CategoryQuery $categoryQuery,
        SearchQuery $searchQuery,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->queryBuilder         = $queryBuilder;
        $this->requestFactory       = $requestFactory;
        $this->queryFactory         = $queryFactory;
        $this->sortOrderBuilder     = $sortOrderBuilder;
        $this->categoryQueryBuilder = $categoryQuery;
        $this->searchQueryBuilder   = $searchQuery;
    }

    /**
     * Build an Elasticsuite Search Request from Search Criteria
     *
     * @param array $requestParams The Request params
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    public function getSearchRequest($requestParams)
    {
        $request = $this->requestFactory->create($requestParams);

        return $request;
    }

    /**
     * Prepare the Search Request Params
     *
     * @param ContainerConfigurationInterface $containerConfig Container Configuration
     * @param CategoryInterface               $category        The category
     * @param string                          $queryText       The query text
     * @param int                             $size            Query Size
     *
     * @return array
     */
    public function getSearchRequestParams(
        ContainerConfigurationInterface $containerConfig,
        $category = null,
        $queryText = null,
        $size = 20
    ) {
        $sortOrders = [];

        $query = $this->queryFactory->create(\Magento\Framework\Search\Request\QueryInterface::TYPE_BOOL, []);

        if ($queryText !== null) {
            $query = $this->searchQueryBuilder->getFullTextQuery($containerConfig, $queryText);
        } elseif ($category !== null) {
            $query      = $this->categoryQueryBuilder->getCategorySearchQuery($containerConfig, $category);
            $sortOrders = $this->categoryQueryBuilder->getCategorySortOrders($category);
        }

        $requestParams = [
            'name'       => $containerConfig->getName(),
            'indexName'  => $containerConfig->getIndexName(),
            'type'       => $containerConfig->getTypeName(),
            'from'       => 0,
            'size'       => $size,
            'dimensions' => [],
            'query'      => $query,
            'sortOrders' => $this->sortOrderBuilder->buildSordOrders($containerConfig, $sortOrders),
            'buckets'    => [],
        ];

        return $requestParams;
    }
}
