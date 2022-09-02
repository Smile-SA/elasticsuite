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
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\LayerBuilderInterface;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\Search\BucketInterface;
use Magento\CatalogGraphQl\DataProvider\Product\LayeredNavigation\Formatter\LayerFormatter;
use Magento\Framework\Phrase;

/**
 * Layered Navigation Builder for Price items.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Price implements LayerBuilderInterface
{
    /**
     * @var string
     */
    const PRICE_BUCKET = 'price.price';

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
     * @var array
     */
    private static $bucketMap = [
        self::PRICE_BUCKET => [
            'request_name' => 'price',
            'label'        => 'Price',
        ],
    ];

    /**
     * @param LayerFormatter      $layerFormatter      Layer Formatter
     * @param AttributeRepository $attributeRepository Attribute Repository
     * @param string              $attributeCode       Attribute code used to load the localized frontend label
     */
    public function __construct(
        LayerFormatter $layerFormatter,
        AttributeRepository $attributeRepository,
        string $attributeCode = 'price'
    ) {
        $this->layerFormatter = $layerFormatter;
        $this->attributeRepository = $attributeRepository;
        $this->attributeCode = $attributeCode;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(AggregationInterface $aggregation, ?int $storeId): array
    {
        $bucket = $aggregation->getBucket(self::PRICE_BUCKET);
        if ($this->isBucketEmpty($bucket)) {
            return [];
        }

        $label = __(self::$bucketMap[self::PRICE_BUCKET]['label']);
        if ($frontendLabel = $this->getFrontendLabel($storeId)) {
            $label = $frontendLabel;
        }
        $result = $this->layerFormatter->buildLayer(
            $label,
            \count($bucket->getValues()),
            self::$bucketMap[self::PRICE_BUCKET]['request_name']
        );

        foreach ($bucket->getValues() as $value) {
            $metrics             = $value->getMetrics();
            $result['options'][] = $this->layerFormatter->buildItem(
                $value->getValue(),
                $value->getValue(),
                $metrics['count']
            );
        }

        $result['has_more'] = false;
        $result['rel_nofollow'] = false;

        return ['price' => $result];
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
