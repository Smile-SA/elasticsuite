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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection\ProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

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
     * @var \Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface
     */
    private $containerConfiguration;

    /**
     * @var \Magento\Catalog\Api\Data\CategoryInterface
     */
    private $category;

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
     * @param OptimizerInterface              $optimizer             The optimizer to preview.
     * @param Preview\ItemFactory             $previewItemFactory    Preview item factory.
     * @param ApplierListFactory              $applier               Preview Applier
     * @param Collection\ProviderFactory      $providerFactory       Optimizer Provider Factory
     * @param ContainerConfigurationInterface $containerConfig       Container Configuration
     * @param Preview\ResultsBuilder          $previewResultsBuilder Preview Results Builder
     * @param CategoryInterface               $category              Category Id to preview, if any.
     * @param string                          $queryText             Query Text.
     * @param int                             $size                  Preview size.
     */
    public function __construct(
        OptimizerInterface $optimizer,
        Preview\ItemFactory $previewItemFactory,
        ApplierListFactory $applier,
        Collection\ProviderFactory $providerFactory,
        ContainerConfigurationInterface $containerConfig,
        Preview\ResultsBuilder $previewResultsBuilder,
        CategoryInterface $category = null,
        $queryText = null,
        $size = 10
    ) {
        $this->size                   = $size;
        $this->previewItemFactory     = $previewItemFactory;
        $this->optimizer              = $optimizer;
        $this->queryText              = $queryText;
        $this->applierListFactory     = $applier;
        $this->providerFactory        = $providerFactory;
        $this->containerConfiguration = $containerConfig;
        $this->previewResultsBuilder  = $previewResultsBuilder;
        $this->category               = $category;
    }

    /**
     * Load preview data.
     *
     * @return array
     */
    public function getData()
    {
        $baseResults  = $this->getBaseResults();
        $baseProducts = $this->preparePreviewItems($baseResults);

        $optimizedResults  = $this->canApply() ? $this->getOptimizedResults() : $baseResults;
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
     * Load non-optimized results.
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getBaseResults()
    {
        $baseApplier = $this->getApplier($this->optimizer, ProviderInterface::TYPE_EXCLUDE);

        return $this->getPreviewResults($baseApplier);
    }

    /**
     * Load optimized results.
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getOptimizedResults()
    {
        $optimizedApplier  = $this->getApplier($this->optimizer, ProviderInterface::TYPE_REPLACE);

        return $this->getPreviewResults($optimizedApplier);
    }

    /**
     * Indicates if the current optimizer can be applied to the search context.
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function canApply() : bool
    {
        if (empty($this->optimizer->getSearchContainer())) {
            return false;
        }

        $canApply = in_array($this->containerConfiguration->getName(), $this->optimizer->getSearchContainer(), true);
        if ($canApply && $this->containerConfiguration->getName() === 'quick_search_container') {
            $config = $this->optimizer->getQuickSearchContainer();
            if ((int) $config['apply_to'] === 1 && !empty($config['query_ids'])) {
                $queries = array_column($config['query_ids'], 'query_text');
                $canApply = in_array($this->queryText, $queries, true);
            }
        } elseif ($canApply && $this->containerConfiguration->getName() === 'catalog_view_container') {
            $config = $this->optimizer->getCatalogViewContainer();
            if ((int) $config['apply_to'] === 1 && !empty($config['category_ids'])) {
                $categoryIds = array_filter($config['category_ids']);
                $canApply = in_array($this->category->getId(), $categoryIds, true);
            }
        }

        return $canApply;
    }

    /**
     * @param ApplierList $applier Optimizer Applier
     *
     * @return \Magento\Framework\Search\ResponseInterface
     */
    private function getPreviewResults($applier)
    {
        return $this->previewResultsBuilder->getPreviewResults(
            $this->containerConfiguration,
            $applier,
            $this->size,
            $this->queryText,
            $this->category
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
