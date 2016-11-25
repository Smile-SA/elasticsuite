<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Plugin;

use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\QueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Plugin that handle query rewriting (synonym substitution) during fulltext query building phase.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class QueryRewrite
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param QueryFactory $queryFactory Search request query factory.
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Rewrite the query.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryBuilder                    $subject         Original query builder.
     * @param \Closure                        $proceed         Original create func.
     * @param ContainerConfigurationInterface $containerConfig Search request container config.
     * @param string                          $queryText       Current query text.
     * @param array                           $filters         Filters.
     * @param string                          $spellingType    Spelling type of the query.
     *
     * @return QueryInterface
     */
    public function aroundCreateQuery(
        QueryBuilder $subject,
        \Closure $proceed,
        ContainerConfigurationInterface $containerConfiguration,
        $queryText,
        $filters,
        $spellingType
    ) {
        $storeId     = $containerConfiguration->getStoreId();
        $requestName = $containerConfiguration->getName();

var_dump('okey66');die;
    }
}