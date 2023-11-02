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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierList;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Optimizer Preview Results builder
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ResultsBuilder
{
    /**
     * @var \Magento\Framework\Search\SearchEngineInterface
     */
    private $searchEngine;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * ResultsBuilder constructor.
     *
     * @param \Magento\Framework\Search\SearchEngineInterface $searchEngine   Search Engine
     * @param RequestBuilder                                  $requestBuilder Request Builder
     */
    public function __construct(
        \Magento\Framework\Search\SearchEngineInterface $searchEngine,
        RequestBuilder $requestBuilder
    ) {
        $this->searchEngine   = $searchEngine;
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     * @param ApplierList                     $applier                Optimizers appliers
     * @param int                             $size                   Size
     * @param string                          $queryText              Query text
     * @param CategoryInterface               $category               Category, if any
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    public function getPreviewResults(
        ContainerConfigurationInterface $containerConfiguration,
        ApplierList $applier,
        $size,
        $queryText = null,
        $category = null
    ) {
        $params = $this->requestBuilder->getSearchRequestParams(
            $containerConfiguration,
            $category,
            $queryText,
            $size
        );

        if (isset($params['query'])) {
            $params['query'] = $applier->applyOptimizers($containerConfiguration, $params['query']);
        }

        return $this->getSearchResults($params);
    }

    /**
     * Execute search.
     *
     * @param array $parameters Search Request Parameters
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getSearchResults($parameters)
    {
        $searchRequest  = $this->requestBuilder->getSearchRequest($parameters);
        $searchResponse = $this->searchEngine->search($searchRequest);

        return $searchResponse;
    }
}
