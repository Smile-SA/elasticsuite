<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model;

/**
 * Build and run a search query in order to build reporting data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractReport implements ReportInterface
{
    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * @var Report\SearchRequestBuilder
     */
    private $searchRequestBuilder;

    /**
     * @var array|null
     */
    private $data = null;

    /**
     * Constructor.
     *
     * @param \Magento\Search\Model\SearchEngine $searchEngine         Search engine.
     * @param Report\SearchRequestBuilder        $searchRequestBuilder Search request builder.
     */
    public function __construct(
        \Magento\Search\Model\SearchEngine $searchEngine,
        Report\SearchRequestBuilder $searchRequestBuilder
    ) {
        $this->searchEngine         = $searchEngine;
        $this->searchRequestBuilder = $searchRequestBuilder;
    }

    /**
     * Get report data.
     *
     * @return array
     */
    public function getData()
    {
        if ($this->data === null) {
            $searchRequest  = $this->searchRequestBuilder->getRequest();
            $searchResponse = $this->searchEngine->search($searchRequest);

            $this->data = $this->processResponse($searchResponse);
        }

        return $this->data;
    }

    /**
     * Process the search response to build report data.
     *
     * @param \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response Search response.
     *
     * @return array
     */
    abstract protected function processResponse(\Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse $response);
}
