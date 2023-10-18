<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Class Search
 *
 * @category Elasticsuite
 * @package  Elasticsuite\CatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Search implements SpecialAttributeInterface
{
    /**
     * @var ContainerConfigurationFactory
     */
    private $containerConfigFactory;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var SpellcheckRequestFactory
     */
    private $spellcheckRequestFactory;

    /**
     * @var SpellcheckerInterface
     */
    private $spellchecker;

    /**
     * Search constructor.
     *
     * @param ContainerConfigurationFactory $containerConfigFactory   Container configuration factory.
     * @param ContextInterface              $searchContext            Search context.
     * @param QueryBuilder                  $queryBuilder             Query builder.
     * @param QueryFactory                  $queryFactory             Query factory.
     * @param SpellcheckRequestFactory      $spellcheckRequestFactory Spellcheck request factory.
     * @param SpellcheckerInterface         $spellchecker             Spellchecker.
     */
    public function __construct(
        ContainerConfigurationFactory $containerConfigFactory,
        ContextInterface $searchContext,
        QueryBuilder $queryBuilder,
        QueryFactory $queryFactory,
        SpellcheckRequestFactory $spellcheckRequestFactory,
        SpellcheckerInterface $spellchecker
    ) {
        $this->containerConfigFactory   = $containerConfigFactory;
        $this->searchContext            = $searchContext;
        $this->queryBuilder             = $queryBuilder;
        $this->queryFactory             = $queryFactory;
        $this->spellcheckRequestFactory = $spellcheckRequestFactory;
        $this->spellchecker             = $spellchecker;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'search';
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQuery(ProductCondition $condition)
    {
        $containerConfiguration = $this->getContainerConfiguration();
        $queryText              = $condition->getValue();
        $searchQuery            = $this->getFullTextQuery($containerConfiguration, $queryText);

        if (substr($condition->getOperator(), 0, 1) === '!') {
            $searchQuery = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $searchQuery]);
        }

        return $searchQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'string';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueName($value)
    {
        if ($value === null || '' === $value) {
            return '...';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Searchable content');
    }

    /**
     * Get fulltext container configuration.
     *
     * @return ContainerConfigurationInterface
     */
    private function getContainerConfiguration()
    {
        return $this->containerConfigFactory->create(
            ['containerName' => 'quick_search_container', 'storeId' => (int) $this->searchContext->getStoreId()]
        );
    }

    /**
     * Retrieve fulltext search query for a given query text
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param string                          $queryText              The query text.
     *
     * @return QueryInterface
     */
    private function getFullTextQuery(ContainerConfigurationInterface $containerConfiguration, $queryText)
    {
        $spellingType = $this->getSpellingType($containerConfiguration, $queryText);

        return $this->queryBuilder->createFulltextQuery($containerConfiguration, $queryText, $spellingType);
    }

    /**
     * Retrieve the spelling type for a fulltext query.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param string                          $queryText              Query text.
     *
     * @return int
     */
    private function getSpellingType(ContainerConfigurationInterface $containerConfiguration, $queryText)
    {
        if (is_array($queryText)) {
            $queryText = implode(" ", $queryText);
        }

        $spellcheckRequestParams = [
            'index'           => $containerConfiguration->getIndexName(),
            'queryText'       => $queryText,
            'cutoffFrequency' => $containerConfiguration->getRelevanceConfig()->getCutOffFrequency(),
            'isUsingAllTokens'  => $containerConfiguration->getRelevanceConfig()->isUsingAllTokens(),
            'isUsingReference'  => $containerConfiguration->getRelevanceConfig()->isUsingReferenceAnalyzer(),
            'isUsingEdgeNgram'  => $containerConfiguration->getRelevanceConfig()->isUsingEdgeNgramAnalyzer(),
        ];

        $spellcheckRequest = $this->spellcheckRequestFactory->create($spellcheckRequestParams);

        return $this->spellchecker->getSpellingType($spellcheckRequest);
    }
}
