<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;

use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Search Query Builder for Optimizer Preview
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchQuery
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
     * @var \Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory
     */
    private $spellcheckRequestFactory;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface
     */
    private $spellchecker;

    /**
     * CategoryQuery constructor.
     *
     * @param QueryBuilder             $queryBuilder             Query Builder
     * @param QueryFactory             $queryFactory             Query Factory
     * @param SpellcheckRequestFactory $spellcheckRequestFactory Spellcheck Request Factory
     * @param SpellcheckerInterface    $spellchecker             Spellchecker
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        SpellcheckRequestFactory $spellcheckRequestFactory,
        SpellcheckerInterface $spellchecker
    ) {
        $this->queryBuilder             = $queryBuilder;
        $this->queryFactory             = $queryFactory;
        $this->spellcheckRequestFactory = $spellcheckRequestFactory;
        $this->spellchecker             = $spellchecker;
    }

    /**
     * Retrieve Fulltext search Query for a given query Text
     *
     * @param ContainerConfigurationInterface $containerConfiguration Search request container configuration.
     * @param string                          $queryText              The Query Text
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getFullTextQuery($containerConfiguration, $queryText)
    {
        $spellingType = $this->getSpellingType($containerConfiguration, $queryText);
        $query        = $this->createFullTextQuery($containerConfiguration, $queryText, $spellingType);
        $filterQuery  = $this->queryBuilder->createFilterQuery($containerConfiguration, $containerConfiguration->getFilters());

        return $this->queryFactory->create(QueryInterface::TYPE_FILTER, ['filter' => $filterQuery, 'query' => $query]);
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
    private function createFullTextQuery(
        ContainerConfigurationInterface $containerConfiguration,
        $queryText,
        $spellingType
    ) {
        $queryParams          = [];
        $queryParams['query'] = $this->queryBuilder->createFulltextQuery($containerConfiguration, $queryText, $spellingType);

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
            'queryText'       => $queryText,
            'cutoffFrequency' => $containerConfig->getRelevanceConfig()->getCutOffFrequency(),
            'isUsingAllTokens'  => $containerConfig->getRelevanceConfig()->isUsingAllTokens(),
            'isUsingReference'  => $containerConfig->getRelevanceConfig()->isUsingReferenceAnalyzer(),
            'isUsingEdgeNgram'  => $containerConfig->getRelevanceConfig()->isUsingEdgeNgramAnalyzer(),
        ];

        $spellcheckRequest = $this->spellcheckRequestFactory->create($spellcheckRequestParams);

        return $this->spellchecker->getSpellingType($spellcheckRequest);
    }
}
