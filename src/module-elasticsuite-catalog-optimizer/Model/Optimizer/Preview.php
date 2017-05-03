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

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
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
     * @var Preview\ItemFactory
     */
    private $previewItemFactory;

    /**
     * @var OptimizerInterface
     */
    private $optimizer;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderFactory
     */
    private $providerFactory;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\ResultsBuilder
     */
    private $previewResultsBuilder;

    /**
     * @var null|string
     */
    private $queryText = null;

    /**
     * @var integer
     */
    private $size;

    /**
     * Constructor.
     *
     * @param OptimizerInterface            $optimizer              The optimizer topreview.
     * @param Preview\ItemFactory           $previewItemFactory     Preview item factory.
     * @param ApplierListFactory            $applier                Preview Applier
     * @param Collection\ProviderFactory    $providerFactory        Optimizer Provider Factory
     * @param ContainerConfigurationFactory $containerConfigFactory Container Configuration Factory
     * @param Preview\ResultsBuilder        $previewResultsBuilder  Preview Results Builder
     * @param string                        $queryText              Query Text.
     * @param int                           $size                   Preview size.
     */
    public function __construct(
        OptimizerInterface $optimizer,
        Preview\ItemFactory $previewItemFactory,
        ApplierListFactory $applier,
        Collection\ProviderFactory $providerFactory,
        ContainerConfigurationFactory $containerConfigFactory,
        Preview\ResultsBuilder $previewResultsBuilder,
        $queryText = null,
        $size = 10
    ) {
        $this->size                   = $size;
        $this->previewItemFactory     = $previewItemFactory;
        $this->optimizer              = $optimizer;
        $this->queryText              = $queryText;
        $this->applierListFactory     = $applier;
        $this->providerFactory        = $providerFactory;
        $this->containerConfigFactory = $containerConfigFactory;
        $this->previewResultsBuilder  = $previewResultsBuilder;
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

        $baseApplier  = $this->getApplier($this->optimizer, ProviderInterface::TYPE_EXCLUDE);
        $baseResults  = $this->getPreviewResults($containerConfig, $baseApplier);
        $baseProducts = $this->preparePreviewItems($baseResults);

        $optimizedApplier  = $this->getApplier($this->optimizer, ProviderInterface::TYPE_REPLACE);
        $optimizedResults  = $this->getPreviewResults($containerConfig, $optimizedApplier);
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
     * @param ContainerConfigurationInterface $containerConfig Container Configuration
     * @param ApplierList                     $applier         Optimizer Applier
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getPreviewResults($containerConfig, $applier)
    {
        return $this->previewResultsBuilder->getPreviewResults(
            $containerConfig,
            $applier,
            $this->queryText,
            $this->size
        );
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

        foreach ($queryResponse->getIterator() as $document) {
            $item                      = $this->previewItemFactory->create(['document' => $document]);
            $items[$document->getId()] = $item->getData();
        }

        return $items;
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
     * Retrieve applier of a given type for an optimizer.
     *
     * @param OptimizerInterface $optimizer    The optimizer
     * @param string             $providerType The provider type
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierList
     */
    private function getApplier(OptimizerInterface $optimizer, $providerType)
    {
        $provider = $this->providerFactory->create($providerType, ['optimizer' => $optimizer]);

        return $this->applierListFactory->create(['collectionProvider' => $provider]);
    }
}
