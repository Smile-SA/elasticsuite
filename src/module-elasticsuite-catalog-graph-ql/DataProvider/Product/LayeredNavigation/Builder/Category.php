<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\DataProvider\Product\LayeredNavigation\Builder;

use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\CatalogGraphQl\DataProvider\Category\Query\CategoryAttributeQuery;
use Magento\CatalogGraphQl\DataProvider\CategoryAttributesMapper;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\Formatter\LayerFormatter;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\LayerBuilderInterface;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\RootCategoryProvider;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\AggregationValueInterface;
use Magento\Framework\Api\Search\BucketInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Layered Navigation Builder for Category items.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Category implements LayerBuilderInterface
{
    /**
     * @var string
     */
    const CATEGORY_BUCKET = 'categories';

    /**
     * @var array
     */
    private static $bucketMap = [
        self::CATEGORY_BUCKET => [
            'request_name' => 'category_id',
            'label'        => 'Category',
        ],
    ];

    /**
     * @var CategoryAttributeQuery
     */
    private $categoryAttributeQuery;

    /**
     * @var CategoryAttributesMapper
     */
    private $attributesMapper;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var RootCategoryProvider
     */
    private $rootCategoryProvider;

    /**
     * @var LayerFormatter
     */
    private $layerFormatter;

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var string
     */
    private $attributeCode;

    /**
     * @param CategoryAttributeQuery   $categoryAttributeQuery   Category Attribute Query
     * @param CategoryAttributesMapper $categoryAttributesMapper Category Attributes Mapper
     * @param RootCategoryProvider     $rootCategoryProvider     Root Category Provider
     * @param LayerFormatter           $layerFormatter           Layer Formatter
     * @param ResourceConnection       $resourceConnection       Resource Connection
     * @param AttributeRepository      $attributeRepository      Product attribute repository
     * @param string                   $attributeCode            Product attribute code used to load the localized frontend label
     */
    public function __construct(
        CategoryAttributeQuery $categoryAttributeQuery,
        CategoryAttributesMapper $categoryAttributesMapper,
        RootCategoryProvider $rootCategoryProvider,
        LayerFormatter $layerFormatter,
        ResourceConnection $resourceConnection,
        AttributeRepository $attributeRepository,
        string $attributeCode = 'category_ids'
    ) {
        $this->categoryAttributeQuery = $categoryAttributeQuery;
        $this->attributesMapper       = $categoryAttributesMapper;
        $this->rootCategoryProvider   = $rootCategoryProvider;
        $this->layerFormatter         = $layerFormatter;
        $this->resourceConnection     = $resourceConnection;
        $this->attributeRepository    = $attributeRepository;
        $this->attributeCode          = $attributeCode;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function build(AggregationInterface $aggregation, ?int $storeId): array
    {
        $bucket = $aggregation->getBucket(self::CATEGORY_BUCKET);

        if ($this->isBucketEmpty($bucket)) {
            return [];
        }

        // The categories bucket is a "filters" aggregation (one query per category): unlike "terms"/"histogram"
        // buckets it has no min_doc_count, so it always returns every declared category, even at 0 products.
        $categoryIds = \array_map(
            function (AggregationValueInterface $value) {
                return (int) $value->getValue();
            },
            \array_filter(
                $bucket->getValues(),
                function (AggregationValueInterface $value) {
                    return ((int) ($value->getMetrics()['count'] ?? 0)) > 0;
                }
            )
        );

        $categoryIds = \array_diff($categoryIds, [$this->rootCategoryProvider->getRootCategory($storeId)]);

        $categoryAttributesValues = $this->attributesMapper->getAttributesValues(
            $this->resourceConnection->getConnection()->fetchAll(
                $this->categoryAttributeQuery->getQuery($categoryIds, ['name', 'is_active'], $storeId)
            )
        );
        $categoryLabels = \array_column($categoryAttributesValues, 'name', 'entity_id');
        $categoryActive = \array_column($categoryAttributesValues, 'is_active', 'entity_id');

        $categoryIds = \array_filter($categoryIds, function ($categoryId) use ($categoryActive) {
            return !empty($categoryActive[$categoryId] ?? null);
        });

        if (!$categoryLabels) {
            return [];
        }

        $label = __(self::$bucketMap[self::CATEGORY_BUCKET]['label']);
        if ($frontendLabel = $this->getFrontendLabel($storeId)) {
            $label = $frontendLabel;
        }
        $result = $this->layerFormatter->buildLayer(
            $label,
            \count($categoryIds),
            self::$bucketMap[self::CATEGORY_BUCKET]['request_name']
        );

        foreach ($bucket->getValues() as $value) {
            $categoryId = $value->getValue();
            if (!\in_array($categoryId, $categoryIds, true)) {
                continue;
            }
            $result['options'][] = $this->layerFormatter->buildItem(
                $categoryLabels[$categoryId] ?? $categoryId,
                $categoryId,
                $value->getMetrics()['count']
            );
        }

        $result['has_more'] = false;

        $attribute = $this->attributeRepository->get($this->attributeCode);
        $result['frontend_input'] = $attribute->getFrontendInput();

        return ['category_id' => $result];
    }

    /**
     * Check that bucket contains data
     *
     * @param BucketInterface|null $bucket Bucket
     *
     * @return bool
     */
    private function isBucketEmpty(?BucketInterface $bucket): bool
    {
        return null === $bucket || !$bucket->getValues();
    }

    /**
     * Return the frontend label of the configured attribute for the given store, if available.
     *
     * @param int|null $storeId Store ID.
     *
     * @return string|null
     */
    private function getFrontendLabel(?int $storeId): ?string
    {
        $label = null;

        try {
            $attribute  = $this->attributeRepository->get($this->attributeCode);
            $label      = $attribute->getDefaultFrontendLabel();
            $frontendLabels = array_filter(
                $attribute->getFrontendLabels(),
                function ($frontendLabel) use ($storeId) {
                    return $frontendLabel->getStoreId() == $storeId;
                }
            );
            if (!empty($frontendLabels)) {
                $label = reset($frontendLabels)->getLabel();
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            ;
        }

        return $label;
    }
}
