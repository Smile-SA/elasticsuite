<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\GraphQl\DataProvider\Product\LayeredNavigation\Builder;

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
 * @category   Smile
 * @package    Smile\ElasticsuiteCatalog
 * @author     Romain Ruaud <romain.ruaud@smile.fr>
 * @deprecated Will be moved to a dedicated module.
 */
class Category // Not implementing the LayerBuilderInterface because it did not exist before Magento 2.3.4.
{
    /**
     * @var string
     */
    private const CATEGORY_BUCKET = 'categories';

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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager      Object Manager
     * @param ResourceConnection                        $resourceConnection Resource Connection
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ResourceConnection $resourceConnection
    ) {
        // Using Object Manager for BC with Magento <2.3.4.
        $this->categoryAttributeQuery = $objectManager->get(CategoryAttributeQuery::class);
        $this->attributesMapper       = $objectManager->get(CategoryAttributesMapper::class);
        $this->rootCategoryProvider   = $objectManager->get(RootCategoryProvider::class);
        $this->layerFormatter         = $objectManager->get(LayerFormatter::class);
        $this->resourceConnection     = $resourceConnection;
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

        $categoryIds = \array_map(
            function (AggregationValueInterface $value) {
                return (int) $value->getValue();
            },
            $bucket->getValues()
        );

        $categoryIds    = \array_diff($categoryIds, [$this->rootCategoryProvider->getRootCategory($storeId)]);
        $categoryLabels = \array_column(
            $this->attributesMapper->getAttributesValues(
                $this->resourceConnection->getConnection()->fetchAll(
                    $this->categoryAttributeQuery->getQuery($categoryIds, ['name'], $storeId)
                )
            ),
            'name',
            'entity_id'
        );

        if (!$categoryLabels) {
            return [];
        }

        $result = $this->layerFormatter->buildLayer(
            self::$bucketMap[self::CATEGORY_BUCKET]['label'],
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

        return [$result];
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
}
