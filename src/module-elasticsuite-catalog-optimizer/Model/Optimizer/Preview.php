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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Search\ResponseInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\ResultsBuilder;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Preview Model for Optimizer
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var ApplierListFactory
     */
    private $applierListFactory;

    /**
     * @var OptimizerInterface
     */
    private $optimizer;

    /**
     * @var ContainerConfigurationInterface
     */
    private $containerConfiguration;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var ProviderFactory
     */
    private $providerFactory;

    /**
     * @var ResultsBuilder
     */
    private $previewResultsBuilder;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * @var null|string
     */
    private $queryText = null;

    /**
     * @var integer
     */
    private $size;

    /**
     * @var array
     */
    private $categoryPreviewContainers;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param OptimizerInterface              $optimizer                 The optimizer to preview.
     * @param Preview\ItemFactory             $previewItemFactory        Preview item factory.
     * @param ApplierListFactory              $applierListFactory        Preview Applier.
     * @param Functions\ProviderFactory       $providerFactory           Optimizer Functions Provider Factory.
     * @param ContainerConfigurationInterface $containerConfig           Container Configuration.
     * @param Preview\ResultsBuilder          $previewResultsBuilder     Preview Results Builder.
     * @param ContextInterface                $searchContext             Search Context.
     * @param CategoryInterface|null          $category                  Category Id to preview, if any.
     * @param string                          $queryText                 Query Text.
     * @param int                             $size                      Preview size.
     * @param array                           $categoryPreviewContainers Category preview compatible containers.
     */
    public function __construct(
        OptimizerInterface $optimizer,
        Preview\ItemFactory $previewItemFactory,
        ApplierListFactory $applierListFactory,
        Functions\ProviderFactory $providerFactory,
        ContainerConfigurationInterface $containerConfig,
        Preview\ResultsBuilder $previewResultsBuilder,
        ContextInterface $searchContext,
        ?CategoryInterface $category = null,
        $queryText = null,
        $size = 10,
        $categoryPreviewContainers = ['catalog_view_container']
    ) {
        $this->size                   = $size;
        $this->previewItemFactory     = $previewItemFactory;
        $this->optimizer              = $optimizer;
        $this->queryText              = $queryText;
        $this->applierListFactory     = $applierListFactory;
        $this->providerFactory        = $providerFactory;
        $this->containerConfiguration = $containerConfig;
        $this->previewResultsBuilder  = $previewResultsBuilder;
        $this->category               = $category;
        $this->searchContext          = $searchContext;
        $this->categoryPreviewContainers = $categoryPreviewContainers;
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
     * @return ResponseInterface
     */
    private function getBaseResults()
    {
        $baseApplier = $this->getApplier($this->optimizer, ProviderInterface::TYPE_EXCLUDE);

        return $this->getPreviewResults($baseApplier);
    }

    /**
     * Load optimized results.
     *
     * @return ResponseInterface
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
            if ((int) ($config['apply_to'] ?? 0) === 1 && !empty($config['query_ids'])) {
                $queryIds = array_column($config['query_ids'], 'id');
                $canApply = ($this->searchContext->getCurrentSearchQuery() !== null) &&
                    in_array($this->searchContext->getCurrentSearchQuery()->getId(), $queryIds, true);
            }
        } elseif ($canApply && in_array($this->containerConfiguration->getName(), $this->categoryPreviewContainers)) {
            $config = $this->optimizer->getCatalogViewContainer();
            if ((int) ($config['apply_to'] ?? 0) === 1 && !empty($config['category_ids'])) {
                $categoryIds = array_filter($config['category_ids']);
                $canApply = in_array($this->category->getId(), $categoryIds, true);
            }
        }

        return $canApply;
    }

    /**
     * @param ApplierList $applier Optimizer Applier
     *
     * @return ResponseInterface
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
     * @param ResponseInterface $queryResponse The Query response, with products as documents.
     *
     * @return Preview\Item[]
     */
    private function preparePreviewItems(ResponseInterface $queryResponse)
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
     * @return ApplierList
     */
    private function getApplier(OptimizerInterface $optimizer, $providerType)
    {
        $provider = $this->providerFactory->create($providerType, ['optimizer' => $optimizer]);

        return $this->applierListFactory->create(['functionsProvider' => $provider]);
    }
}
