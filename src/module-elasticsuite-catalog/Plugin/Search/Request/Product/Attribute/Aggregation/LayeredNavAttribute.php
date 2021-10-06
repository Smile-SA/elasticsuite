<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCAtalog\Plugin\Search\Request\Product\Attribute\Aggregation;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Smile\ElasticsuiteCatalog\Api\LayeredNavAttributeInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\LayeredNavAttributesProvider;

/**
 * Class LayeredNavAttribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class LayeredNavAttribute
{
    /**
     * @var LayeredNavAttributesProvider
     */
    private $layeredNavAttributesProvider;

    /**
     * LayeredNavAttribute Constructor.
     *
     * @param LayeredNavAttributesProvider $layeredNavAttributesProvider Layered navigation Attributes Provider.
     */
    public function __construct(
        LayeredNavAttributesProvider $layeredNavAttributesProvider
    ) {
        $this->layeredNavAttributesProvider = $layeredNavAttributesProvider;
    }

    /**
     * Replace filter field value for layered navigation  attributes.
     *
     * @param AggregationInterface $subject   Plugin subject.
     * @param array                $result    Result.
     * @param Attribute            $attribute Attribute.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return array
     */
    public function afterGetAggregationData(AggregationInterface $subject, array $result, Attribute $attribute): array
    {
        $layeredNavAttribute = $this->layeredNavAttributesProvider->getLayeredNavAttribute(
            $attribute->getAttributeCode()
        );
        if ($layeredNavAttribute instanceof LayeredNavAttributeInterface) {
            $result['name'] = $layeredNavAttribute->getFilterField();
            $result = array_merge($result, $layeredNavAttribute->getAdditionalAggregationData());
        }

        return $result;
    }
}
