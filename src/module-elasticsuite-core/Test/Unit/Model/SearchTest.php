<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Model;

use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Search API unit testing.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SearchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test search response format.
     *
     * @dataProvider dataprovider
     *
     * @param unknown $documents Search engine hits.
     * @param unknown $docCount  Total number of docs that match the search.
     *
     * @return void.
     */
    public function testSearch($documents, $docCount)
    {
        $searchEngine          = $this->getSearchEngine($documents, $docCount);
        $searchRequestBuilder  = $this->getSearchRequestBuilder();
        $searchResponseBuilder = $this->getSearchResponseBuilder();
        $searchContext         = $this->createMock(ContextInterface::class);

        $searchApi = new \Smile\ElasticsuiteCore\Model\Search($searchEngine, $searchRequestBuilder, $searchResponseBuilder, $searchContext);

        $searchCriteria = $this->createMock(\Smile\ElasticsuiteCore\Api\Search\SearchCriteriaInterface::class);
        $searchResponse = $searchApi->search($searchCriteria);

        $this->assertEquals($docCount, $searchResponse->getTotalCount());
        $this->assertEquals($documents, $searchResponse->getItems());
        $this->assertEquals($searchCriteria, $searchResponse->getSearchCriteria());
        $this->assertInstanceOf(\Magento\Framework\Api\Search\AggregationInterface::class, $searchResponse->getAggregations());
    }

    /**
     * Search test dataprovider.
     *
     * @return array
     */
    public function dataProvider()
    {
        $data = [[[], 0], [['doc1'], 1], [['doc1'], 2], [['doc1', 'doc2'], 2]];

        return $data;
    }

    /**
     * Build a search engine mock.
     *
     * @param array   $documents Documents returns when a search is issued.
     * @param integer $docCount  Number of documents returns when a search is issued.
     *
     * @return \Magento\Framework\Search\SearchEngineInterface
     */
    private function getSearchEngine($documents, $docCount)
    {
        $aggregations = $this->createMock(\Magento\Framework\Api\Search\AggregationInterface::class);

        $searchResult = $this->createMock(\Magento\Framework\Search\ResponseInterface::class);
        $searchResult->expects($this->any())->method('getIterator')->willReturn(new \ArrayIterator($documents));
        $searchResult->expects($this->any())->method('count')->willReturn($docCount);
        $searchResult->expects($this->any())->method('getAggregations')->willReturn($aggregations);

        $searchEngine = $this->createMock(\Magento\Framework\Search\SearchEngineInterface::class);
        $searchEngine->expects($this->any())->method('search')->willReturn($searchResult);

        return $searchEngine;
    }

    /**
     * Search request builder mock.
     *
     * @return \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    private function getSearchRequestBuilder()
    {
        $searchRequest = $this->createMock(\Smile\ElasticsuiteCore\Search\RequestInterface::class);
        $searchRequestBuilder = $this->createMock(\Smile\ElasticsuiteCore\Model\Search\RequestBuilder::class);
        $searchRequestBuilder->expects($this->any())->method('getRequest')->willReturn($searchRequest);

        return $searchRequestBuilder;
    }

    /**
     * Search response builder mock.
     *
     * @return \Magento\Framework\Search\SearchResponseBuilder
     */
    private function getSearchResponseBuilder()
    {
        $documentFactory = $this->createMock(\Magento\Framework\Api\Search\DocumentFactory::class);
        $searchResultFactory = $this->createMock(\Magento\Framework\Api\Search\SearchResultFactory::class);
        $searchResult = new \Magento\Framework\Api\Search\SearchResult();
        $searchResultFactory->expects($this->any())->method('create')->willReturn($searchResult);

        return new \Magento\Framework\Search\SearchResponseBuilder($searchResultFactory, $documentFactory);
    }
}
