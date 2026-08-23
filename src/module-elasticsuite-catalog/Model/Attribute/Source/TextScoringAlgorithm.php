<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Attribute\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Source model for available text scoring models/similarities for searchable attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 */
class TextScoringAlgorithm implements OptionSourceInterface
{
    /**
     * Return array of boolean logic operator.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Default'), 'value' => FieldInterface::SIMILARITY_DEFAULT],
            ['label' => __('Boolean'), 'value' => FieldInterface::SIMILARITY_BOOLEAN],
        ];
    }
}
