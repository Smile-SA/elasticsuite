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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Magento\Search\Model\SearchEngine;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as FulltextCollectionFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\ItemFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;

/**
 * Preview Model for Optimizer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Preview
{
    /**
     * @var ItemFactory
     */
    private $previewItemFactory;

    /**
     * @var OptimizerInterface
     */
    private $optimizer;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\RequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ApplierList
     */
    private $applier;

    /**
     * @var null|string
     */
    private $queryText = null;

    /**
     * Constructor.
     *
     * @param OptimizerInterface            $optimizer              The optimizer to preview.
     * @param ItemFactory                   $previewItemFactory     Preview item factory.
     * @param SearchEngine                  $searchEngine           Search Engine
     * @param Preview\RequestBuilder        $requestBuilder         Request Builder
     * @param ApplierList                   $applier                Preview Applier
     * @param ContainerConfigurationFactory $containerConfigFactory Container Configuration
     * @param string                        $queryText              Query Text.
     * @param int                           $size                   Preview size.
     */
    public function __construct(
        OptimizerInterface $optimizer,
        ItemFactory $previewItemFactory,
        SearchEngine $searchEngine,
        Preview\RequestBuilder $requestBuilder,
        ApplierList $applier,
        ContainerConfigurationFactory $containerConfigFactory,
        $queryText = null,
        $size = 10
    ) {
        $this->size                   = $size;
        $this->previewItemFactory     = $previewItemFactory;
        $this->optimizer              = $optimizer;
        $this->queryText              = $queryText;
        $this->searchEngine           = $searchEngine;
        $this->requestBuilder         = $requestBuilder;
        $this->applier                = $applier;
        $this->containerConfigFactory = $containerConfigFactory;
    }

    /**
     * Load preview data.
     *
     * @return array
     */
    public function getData()
    {
        $containerConfig = $this->containerConfigFactory->create(
            ['containerName' => 'quick_search_container', 'storeId' => $this->optimizer->getStoreId()]
        );

        $baseResults       = $this->getBaseProductsResults($containerConfig, $this->optimizer, $this->queryText);
        $baseProducts      = $this->preparePreviewItems($baseResults);
        $optimizedResults  = $this->getOptimizedProductsResults($containerConfig, $this->optimizer, $this->queryText);
        $optimizedProducts = $this->preparePreviewItems($optimizedResults);

        $effectFunction = function ($document) use ($baseProducts, $optimizedProducts) {
            $document['effect'] = $this->getEffectOnProduct(
                array_keys($baseProducts),
                array_keys($optimizedProducts),
                $document['id']
            );

            return $document;
        };

        $optimizedProducts = array_map($effectFunction, $optimizedProducts);

        $data = [
            'base_products'      => array_values($baseProducts),
            'optimized_products' => array_values($optimizedProducts),
            'size'               => max($baseResults->count(), $optimizedResults->count()), // Should be the same.
        ];

        return $data;
    }

    /**
     * Retrieve results without this optimizer.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     * @param OptimizerInterface              $optimizer              Optimizer
     * @param string                          $queryText              Query Text
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getBaseProductsResults(
        ContainerConfigurationInterface $containerConfiguration,
        OptimizerInterface $optimizer,
        $queryText = ''
    ) {
        $params = $this->requestBuilder->buildSearchRequestParams(
            $containerConfiguration,
            $queryText,
            $this->size
        );

        if (isset($params['query'])) {
            $params['query'] = $this->applier->applyAllExcept($containerConfiguration, $params['query'], $optimizer);
        }

        return $this->getSearchResults($params);
    }

    /**
     * Retrieve results with this optimizer.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container Configuration
     * @param OptimizerInterface              $optimizer              Optimizer
     * @param string                          $queryText              Query Text
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getOptimizedProductsResults(
        ContainerConfigurationInterface $containerConfiguration,
        OptimizerInterface $optimizer,
        $queryText = ''
    ) {
        $params = $this->requestBuilder->buildSearchRequestParams(
            $containerConfiguration,
            $queryText,
            $this->size
        );

        if (isset($params['query'])) {
            $params['query'] = $this->applier->applyNewOptimizer($containerConfiguration, $params['query'], $optimizer);
        }

        return $this->getSearchResults($params);
    }

    /**
     * Convert an array of products to an array of preview items.
     *
     * @param \Magento\Framework\Search\ResponseInterface $queryResponse The Query response, with products as documents.
     *
     * @return Preview\Item[]
     */
    private function preparePreviewItems(\Magento\Framework\Search\ResponseInterface $queryResponse)
    {
        $items = [];

        /** @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Document $document */
        foreach ($queryResponse->getIterator() as $document) {
            $item                      = $this->previewItemFactory->create(['document' => $document]);
            $items[$document->getId()] = $item->getData();
        }

        return $items; //['products' => $items, 'size' => $queryResponse->count()];
    }

    /**
     * Indicates if the product position have moved after optimization.
     *
     * @param array $baseProductIds      The base product Ids
     * @param array $optimizedProductIds The optimized product Ids.
     * @param int   $productId           The current product.
     *
     * @return int
     */
    private function getEffectOnProduct($baseProductIds, $optimizedProductIds, $productId)
    {
        $result                   = 0;
        $baseProductPosition      = array_search($productId, $baseProductIds);
        $optimizedProductPosition = array_search($productId, $optimizedProductIds);

        if ($baseProductPosition === false && $optimizedProductPosition !== false) {
            $result = 1;
        } elseif ($baseProductPosition !== false && $optimizedProductPosition === false) {
            $result = -1;
        } elseif ($baseProductPosition !== false && $optimizedProductPosition !== false) {
            if ($baseProductPosition > $optimizedProductPosition) {
                $result = 1;
            }
            if ($baseProductPosition < $optimizedProductPosition) {
                $result = -1;
            }
        }

        return $result;
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
