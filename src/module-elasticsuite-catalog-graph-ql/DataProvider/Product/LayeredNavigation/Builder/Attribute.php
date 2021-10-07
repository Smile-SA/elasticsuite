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
use Smile\ElasticsuiteCatalog\Model\Attribute\SpecialAttributesProvider;

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
     * @var SpecialAttributesProvider
     */
    protected $specialAttributesProvider;

    /**
     * @var array
     */
    private $bucketNameFilter = [
        Price::PRICE_BUCKET,
        Category::CATEGORY_BUCKET,
        'attribute_set_id',
    ];

    /**
     * @param LayerFormatter            $layerFormatter            Layer Formatter.
     * @param AttributeRepository       $attributeRepository       Attribute Repository.
     * @param SpecialAttributesProvider $specialAttributesProvider Special Attributes Provider.
     * @param array                     $bucketNameFilter          Bucket Filter.
     */
    public function __construct(
        LayerFormatter $layerFormatter,
        AttributeRepository $attributeRepository,
        SpecialAttributesProvider $specialAttributesProvider,
        $bucketNameFilter = []
    ) {
        $this->layerFormatter            = $layerFormatter;
        $this->bucketNameFilter          = \array_merge($this->bucketNameFilter, $bucketNameFilter);
        $this->attributeRepository       = $attributeRepository;
        $this->specialAttributesProvider = $specialAttributesProvider;
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
            $attributeCode = $this->specialAttributesProvider->getSpecialAttributeByFilterField($bucketName) ?? $attributeCode;

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

            $hasMore = false;
            $count   = \count($bucket->getValues());
            $options = [];
            foreach ($bucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                if ($value->getValue() === '__other_docs') {
                    $count += ((int) $metrics['count'] ?? 0) - 1; // -1 because '__other_docs' is counted in.
                    $hasMore = true;
                    continue;
                }

                $options[] = $this->layerFormatter->buildItem($value->getValue(), $value->getValue(), $metrics['count']);
            }

            $result[$attributeCode] = $this->layerFormatter->buildLayer($label, $count, $attributeCode);
            $result[$attributeCode]['options']  = $options;
            $result[$attributeCode]['has_more'] = $hasMore;
            $result[$attributeCode]['rel_nofollow'] = (bool) $attribute->getIsDisplayRelNofollow();

            if ($attributeCode !== 'attribute_set_id' &&
                $attribute->getFacetSortOrder() == ElasticBucketInterface::SORT_ORDER_MANUAL) {
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
