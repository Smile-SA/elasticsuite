<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;

use Magento\Search\Model\SearchEngine;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
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
     * @var \Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory
     */
    private $spellcheckRequestFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface
     */
    private $spellchecker;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory
     */
    private $containerConfigFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\RequestFactory
     */
    private $requestFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * RequestBuilder constructor.
     *
     * @param RequestFactory                $requestFactory           Request Factory
     * @param QueryBuilder                  $queryBuilder             Query Builder
     * @param SpellcheckRequestFactory      $spellcheckRequestFactory Spellcheck Request Factory
     * @param SpellcheckerInterface         $spellchecker             Spellchecker
     * @param ContainerConfigurationFactory $containerConfigFactory   Container Configuration
     * @param QueryFactory                  $queryFactory             Query Factory
     */
    public function __construct(
        RequestFactory $requestFactory,
        QueryBuilder $queryBuilder,
        SpellcheckRequestFactory $spellcheckRequestFactory,
        SpellcheckerInterface $spellchecker,
        ContainerConfigurationFactory $containerConfigFactory,
        QueryFactory $queryFactory
    ) {
        $this->queryBuilder             = $queryBuilder;
        $this->spellcheckRequestFactory = $spellcheckRequestFactory;
        $this->spellchecker             = $spellchecker;
        $this->containerConfigFactory   = $containerConfigFactory;
        $this->requestFactory           = $requestFactory;
        $this->queryFactory             = $queryFactory;
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
     * @param string                          $queryText       The query text
     * @param int                             $size            Query Size
     *
     * @return array
     */
    public function getSearchRequestParams(ContainerConfigurationInterface $containerConfig, $queryText, $size = 20)
    {
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;

        if ($queryText) {
            $spellingType = $this->getSpellingType($containerConfig, $queryText);
        }

        $requestParams = [
            'name'         => $containerConfig->getName(),
            'indexName'    => $containerConfig->getIndexName(),
            'type'         => $containerConfig->getTypeName(),
            'from'         => 0,
            'size'         => $size,
            'dimensions'   => [],
            'query'        => $this->createQuery($containerConfig, $queryText, $spellingType),
            'sortOrders'   => null,
            'buckets'      => [],
            'spellingType' => $spellingType,
        ];

        return $requestParams;
    }

    /**
     * Create a filtered query with an optional fulltext query part.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param string|null                     $queryText              Fulltext query.
     * @param string                          $spellingType           For fulltext query : the type of spellchecked
     *                                                                applied.
     *
     * @return QueryInterface
     */
    private function createQuery(ContainerConfigurationInterface $containerConfiguration, $queryText, $spellingType)
    {
        $queryParams = [];

        if ($queryText) {
            $queryParams['query'] = $this->queryBuilder->createFulltextQuery($containerConfiguration, $queryText, $spellingType);
        }

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, $queryParams);
    }

    /**
     * Retireve the spelling type for a fulltext query.
     *
     * @param ContainerConfigurationInterface $containerConfig Search request configuration.
     * @param string                          $queryText       Query text.
     *
     * @return int
     */
    private function getSpellingType(ContainerConfigurationInterface $containerConfig, $queryText)
    {
        if (is_array($queryText)) {
            $queryText = implode(" ", $queryText);
        }

        $spellcheckRequestParams = [
            'index'           => $containerConfig->getIndexName(),
            'type'            => $containerConfig->getTypeName(),
            'queryText'       => $queryText,
            'cutoffFrequency' => $containerConfig->getRelevanceConfig()->getCutOffFrequency(),
        ];

        $spellcheckRequest = $this->spellcheckRequestFactory->create($spellcheckRequestParams);
        $spellingType      = $this->spellchecker->getSpellingType($spellcheckRequest);

        return $spellingType;
    }
}
