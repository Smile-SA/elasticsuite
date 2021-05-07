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
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\Formatter\LayerFormatter;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\LayerBuilderInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\BucketInterface;
use Smile\ElasticsuiteCore\Helper\Mapping;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface as ElasticBucketInterface;

/**
 * Layered Navigation Builder for Default Attribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Attribute implements LayerBuilderInterface
{
    /**
     * @var LayerFormatter
     */
    private $layerFormatter;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Repository
     */
    private $attributeRepository;

    /**
     * @var array
     */
    private $bucketNameFilter = [
        Price::PRICE_BUCKET,
        Category::CATEGORY_BUCKET,
    ];

    /**
     * @param LayerFormatter      $layerFormatter      Layer Formatter
     * @param AttributeRepository $attributeRepository Attribute Repository
     * @param array               $bucketNameFilter    Bucket Filter
     */
    public function __construct(
        LayerFormatter $layerFormatter,
        AttributeRepository $attributeRepository,
        $bucketNameFilter = []
    ) {
        $this->layerFormatter      = $layerFormatter;
        $this->bucketNameFilter    = \array_merge($this->bucketNameFilter, $bucketNameFilter);
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Zend_Db_Statement_Exception
     */
    public function build(AggregationInterface $aggregation, ?int $storeId): array
    {
        $prefix = Mapping::OPTION_TEXT_PREFIX . '_';
        $result = [];

        foreach ($this->getAttributeBuckets($aggregation) as $bucket) {
            $bucketName    = $bucket->getName();
            $attributeCode = $bucketName;
            if (substr($bucketName, 0, strlen($prefix)) === $prefix) {
                $attributeCode = substr($bucketName, strlen($prefix));
            }

            $label = $attributeCode;
            try {
                $attribute      = $this->attributeRepository->get($attributeCode);
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
                $label = $attributeCode;
            }

            $result[$attributeCode] = $this->layerFormatter->buildLayer(
                $label,
                \count($bucket->getValues()),
                $attributeCode
            );

            foreach ($bucket->getValues() as $value) {
                $metrics                             = $value->getMetrics();
                $result[$attributeCode]['options'][] = $this->layerFormatter->buildItem(
                    $attribute['options'][$value->getValue()] ?? $value->getValue(),
                    $value->getValue(),
                    $metrics['count']
                );
            }

            $isManualOrder = $attribute->getFacetSortOrder() == ElasticBucketInterface::SORT_ORDER_MANUAL;
            if ($isManualOrder && $attributeCode !== 'attribute_set_id') {
                $items = array_column($result[$attributeCode]['options'], null, 'label');
                $options = $attribute->getFrontend()->getSelectOptions();

                $result[$attributeCode]['options'] = $this->addOptionsData($items, $options);
            }
        }

        return $result;
    }

    /**
     * Resort items according option position defined in admin.
     *
     * @param array $items   Items to be sorted.
     * @param array $options Options of attribute.
     *
     * @return array
     */
    private function addOptionsData(array $items, $options)
    {
        $optionPosition = 0;
        if (!empty($options)) {
            foreach ($options as $option) {
                if (isset($option['label']) && !empty($option['label'])) {
                    $optionLabel = trim((string) $option['label']);
                    $optionPosition++;

                    if (isset($items[$optionLabel])) {
                        $items[$optionLabel]['adminSortIndex'] = $optionPosition;
                        $items[$optionLabel]['value']          = $optionLabel;
                    }
                }
            }

            $items = $this->sortOptionsData($items);
        }

        return $items;
    }

    /**
     * Sort items by adminSortIndex key.
     *
     * @param array $items to be sorted.
     *
     * @return array
     */
    private function sortOptionsData(array $items)
    {
        usort($items, function ($item1, $item2) {
            if (!isset($item1['adminSortIndex']) or !isset($item2['adminSortIndex'])) {
                return 0;
            }

            return $item1['adminSortIndex'] <= $item2['adminSortIndex'] ? -1 : 1;
        });

        return $items;
    }

    /**
     * Get attribute buckets excluding specified bucket names
     *
     * @param AggregationInterface $aggregation Aggregation
     *
     * @return \Generator|BucketInterface[]
     */
    private function getAttributeBuckets(AggregationInterface $aggregation)
    {
        foreach ($aggregation->getBuckets() as $bucket) {
            if (\in_array($bucket->getName(), $this->bucketNameFilter, true)) {
                continue;
            }
            if ($this->isBucketEmpty($bucket)) {
                continue;
            }
            yield $bucket;
        }
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
