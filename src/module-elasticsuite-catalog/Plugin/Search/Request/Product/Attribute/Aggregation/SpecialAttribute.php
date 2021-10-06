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
use Smile\ElasticsuiteCatalog\Api\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\SpecialAttributesProvider;

/**
 * Class SpecialAttribute.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class SpecialAttribute
{
    /**
     * @var SpecialAttributesProvider
     */
    private $specialAttributesProvider;

    /**
     * SpecialAttribute Constructor.
     *
     * @param SpecialAttributesProvider $specialAttributesProvider Special Attributes Provider.
     */
    public function __construct(
        SpecialAttributesProvider $specialAttributesProvider
    ) {
        $this->specialAttributesProvider = $specialAttributesProvider;
    }

    /**
     * Replace filter field value for special attributes.
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
        $specialAttribute = $this->specialAttributesProvider->getSpecialAttribute($attribute->getAttributeCode());
        if ($specialAttribute instanceof SpecialAttributeInterface) {
            $result['name'] = $specialAttribute->getFilterField();
            $result = array_merge($result, $specialAttribute->getAdditionalAggregationData());
        }

        return $result;
    }
}
