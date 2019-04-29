<?php
/**
 * DISCLAIMER :
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
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\Aggregation;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Default aggregation builder for product attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultAttribute implements AggregationInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $mappingHelper;

    /**
     * DefaultAttribute constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper Mapping Helper
     */
    public function __construct(\Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper)
    {
        $this->mappingHelper = $mappingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregationData(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $bucketConfig = [
            'name'      => $this->getFilterField($attribute),
            'size'      => (int) $attribute->getFacetMaxSize(),
            'type'      => \Smile\ElasticsuiteCore\Search\Request\BucketInterface::TYPE_TERM,
            'sortOrder' => $attribute->getFacetSortOrder(),
        ];

        $isManualOrder = $attribute->getFacetSortOrder() == BucketInterface::SORT_ORDER_MANUAL;

        if ($isManualOrder) {
            $bucketConfig['size'] = 0;
        }

        return $bucketConfig;
    }

    /**
     * Retrieve ES filter field.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute The attribute
     *
     * @return string
     */
    protected function getFilterField(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        return $this->mappingHelper->getFilterField($attribute);
    }
}
