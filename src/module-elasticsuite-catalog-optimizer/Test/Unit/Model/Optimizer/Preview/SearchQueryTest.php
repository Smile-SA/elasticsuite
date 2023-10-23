<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Model\Optimizer\Preview;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\SearchQuery;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Spellchecker\Request as SpellcheckerRequest;

/**
 * Optimiser Preview Search Query unit testing.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class SearchQueryTest extends TestCase
{
    /**
     * Tests the correct creation of a SpellcheckerInterface with regards to parameters
     * (introduction/removal of experimental relevance settings)
     * @covers \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\SearchQuery::getSpellingType
     *
     * @return void
     */
    public function testSpellcheckRequestConstructor(): void
    {
        $searchQueryPreview = new SearchQuery(
            $this->getQueryBuilderMock(),
            $this->getQueryFactoryMock(),
            $this->getSpellcheckerRequestFactoryMock(),
            $this->getSpellcheckerInterfaceMock()
        );

        $fulltextQuery = $searchQueryPreview->getFullTextQuery(
            $this->getContainerConfigurationInterfaceMock(),
            'test'
        );
        $this->assertEquals([], $fulltextQuery);

        $fulltextQuery = $searchQueryPreview->getFullTextQuery(
            $this->getContainerConfigurationInterfaceMock(),
            ['test1', 'test2']
        );
        $this->assertEquals([], $fulltextQuery);
    }

    /**
     * Get Query Builder mock object
     *
     * @return MockObject
     */
    private function getQueryBuilderMock(): MockObject
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->method('createFulltextQuery')->willReturn([]);
        $queryBuilder->method('createFilterQuery')->willReturn([]);

        return $queryBuilder;
    }

    /**
     * Get Query Factory mock object
     *
     * @return MockObject
     */
    private function getQueryFactoryMock(): MockObject
    {
        $queryFactory = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queryFactory->method('create')->willReturn([]);

        return $queryFactory;
    }

    /**
     * Get Spellchecker Request Factory mock object
     *
     * @return MockObject
     */
    private function getSpellcheckerRequestFactoryMock(): MockObject
    {
        $spellcheckRequestFactory = $this->getMockBuilder(SpellcheckRequestFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $spellcheckRequestFactory->method('create')->willReturnCallback(function ($args) {
            return new SpellcheckerRequest(...array_values($args));
        });

        return $spellcheckRequestFactory;
    }

    /**
     * Get Container Configuration mock object
     *
     * @return MockObject
     */
    private function getContainerConfigurationInterfaceMock(): MockObject
    {
        $containerConfiguration = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $containerConfiguration->method('getIndexName')->willReturn('Dummy');
        $containerConfiguration->method('getRelevanceConfig')->willReturn($this->getRelevanceConfigurationInterfaceMock());
        $containerConfiguration->method('getFilters')->willReturn([]);

        return $containerConfiguration;
    }

    /**
     * Get Relevance Configuration mock object
     *
     * @return MockObject
     */
    private function getRelevanceConfigurationInterfaceMock(): MockObject
    {
        $relevanceConfiguration = $this->getMockBuilder(RelevanceConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $relevanceConfiguration->method('getCutOffFrequency')->willReturn(0.15);
        $relevanceConfiguration->method('isUsingAllTokens')->willReturn(false);
        $relevanceConfiguration->method('isUsingReferenceAnalyzer')->willReturn(false);
        $relevanceConfiguration->method('isUsingEdgeNgramAnalyzer')->willReturn(false);

        return $relevanceConfiguration;
    }

    /**
     * Get Spellchecker mock object
     *
     * @return MockObject
     */
    private function getSpellcheckerInterfaceMock(): MockObject
    {
        $spellChecker = $this->getMockBuilder(SpellcheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $spellChecker->method('getSpellingType')->willReturn(SpellcheckerInterface::SPELLING_TYPE_EXACT);

        return $spellChecker;
    }
}
