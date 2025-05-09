<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request;

use Magento\Framework\Search\Request\DimensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfiguration\AggregationResolverInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Search\Spellchecker\RequestInterfaceFactory as SpellcheckRequestFactory;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\Aggregation\AggregationBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;
use Smile\ElasticsuiteCore\Search\Request\SortOrder\SortOrderBuilder;
use Smile\ElasticsuiteCore\Search\RequestFactory;
use Smile\ElasticsuiteCore\Search\Spellchecker\Request as SpellcheckerRequest;

/**
 * Search Request Builder unit testing.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 */
class BuilderTest extends TestCase
{
    /**
     * Tests the correct creation of a SpellcheckerInterface with regards to parameters
     * (introduction/removal of experimental relevance settings)
     * @covers \Smile\ElasticsuiteCore\Search\Request\Builder::getSpellingType
     *
     * @return void
     */
    public function testSpellcheckRequestConstructor(): void
    {
        $requestBuilder = new Builder(
            $this->getSearchRequestFactoryMock(),
            $this->getDimensionFactoryMock(),
            $this->getQueryBuilderMock(),
            $this->getSortOrderBuilderMock(),
            $this->getAggregationBuilderMock(),
            $this->getContainerConfigurationFactoryMock(),
            $this->getSpellcheckerRequestFactoryMock(),
            $this->getSpellcheckerInterfaceMock(),
            $this->getAggregationResolverMock()
        );


        $request = $requestBuilder->create(
            '1',
            'quick_search_container',
            0,
            1,
            'test'
        );
        $this->assertEquals([], $request);

        $request = $requestBuilder->create(
            '1',
            'quick_search_container',
            0,
            1,
            ['test1', 'test2']
        );
        $this->assertEquals([], $request);
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
     * Get Spellchecker Request Factory mock object
     *
     * @return MockObject
     */
    private function getSpellcheckerRequestFactoryMock(): MockObject
    {
        $spellcheckRequestFactory = $this->getMockBuilder(SpellcheckRequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $spellcheckRequestFactory->method('create')->willReturnCallback(function ($args) {
            return new SpellcheckerRequest(...array_values($args));
        });

        return $spellcheckRequestFactory;
    }

    /**
     * Get Container Configuration Factory mock object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return MockObject
     */
    private function getContainerConfigurationFactoryMock(): MockObject
    {
        $containerConfigurationFactory = $this->getMockBuilder(ContainerConfigurationFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $containerConfigurationFactory->method('create')->willReturnCallback(function ($args) {
            return $this->getContainerConfigurationInterfaceMock();
        });

        return $containerConfigurationFactory;
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

    /**
     * Get Search Request Factory mock object
     *
     * @return MockObject
     */
    private function getSearchRequestFactoryMock(): MockObject
    {
        $requestFactory = $this->getMockBuilder(RequestFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $requestFactory->method('create')->willReturn([]);

        return $requestFactory;
    }

    /**
     * Get Dimension Factory mock object
     *
     * @return MockObject
     */
    private function getDimensionFactoryMock(): MockObject
    {
        $dimensionFactory = $this->getMockBuilder(DimensionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $dimensionFactory->method('create')->willReturn([]);

        return $dimensionFactory;
    }

    /**
     * Get Sort Order Builder mock object
     *
     * @return MockObject
     */
    private function getSortOrderBuilderMock(): MockObject
    {
        $sortOrderBuilder = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sortOrderBuilder->method('buildSordOrders')->willReturn([]);

        return $sortOrderBuilder;
    }

    /**
     * Get Aggregation Builder mock object
     *
     * @return MockObject
     */
    private function getAggregationBuilderMock(): MockObject
    {
        $sortOrderBuilder = $this->getMockBuilder(AggregationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sortOrderBuilder->method('buildAggregations')->willReturn([]);

        return $sortOrderBuilder;
    }

    /**
     * Get Aggregation Resolver mock object
     *
     * @return MockObject
     */
    private function getAggregationResolverMock(): MockObject
    {
        $aggregationResolver = $this->getMockBuilder(AggregationResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $aggregationResolver->method('getContainerAggregations')->willReturn([]);

        return $aggregationResolver;
    }
}
