<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute;

/**
 * Product Aggregations Resolver.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AggregationResolver
{
    /**
     * @var AggregationInterface[]
     */
    private $aggregations;

    /**
     * @var AggregationInterface
     */
    private $defaultAggregation;

    /**
     * @param AggregationInterface   $defaultAggregation Default implementation of attributes aggregations.
     * @param AggregationInterface[] $aggregations       Additional aggregations
     */
    public function __construct(AggregationInterface $defaultAggregation, array $aggregations = [])
    {
        $this->defaultAggregation = $defaultAggregation;
        $this->aggregations       = $aggregations;
    }

    /**
     * Get Aggregation Data for a given product attribute.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute Product EAV Attribute
     *
     * @return array
     */
    public function getAggregationData(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $type        = $attribute->getBackendType();
        $aggregation = isset($this->aggregations[$type]) ? $this->aggregations[$type] : $this->defaultAggregation;

        if ($attribute->getBackendType() == 'varchar' && $attribute->getFrontendClass() == 'validate-number') {
            $aggregation = $this->aggregations['decimal'];
        }

        if ($attribute->getBackendType() == 'static' && $attribute->getAttributeCode() == 'category_ids') {
            $aggregation = $this->aggregations['category'];
        }

        if ($attribute->getAttributeCode() == 'price') {
            $aggregation = $this->aggregations['price'];
        }

        if (!($aggregation instanceof AggregationInterface)) {
            throw new \InvalidArgumentException(
                'Aggregation must implement ' . AggregationInterface::class
            );
        }

        return $aggregation->getAggregationData($attribute);
    }
}
