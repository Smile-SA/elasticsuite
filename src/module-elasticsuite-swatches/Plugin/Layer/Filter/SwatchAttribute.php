<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteSwatches
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteSwatches\Plugin\Layer\Filter;

/**
 * Plugin to set default facet_max_size of swatches attributes to 0 for layered navigation.
 *
 * @category Smile
 * @package  Smile\ElasticSuiteSwatches
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SwatchAttribute
{
    /**
     * @var \Magento\Swatches\Model\SwatchAttributeType
     */
    private $swatchAttributeType;

    /**
     * ProductAttribute constructor.
     *
     * @param \Magento\Swatches\Model\SwatchAttributeType $swatchAttributeType Swatch Attribute Type
     */
    public function __construct(\Magento\Swatches\Model\SwatchAttributeType $swatchAttributeType)
    {
        $this->swatchAttributeType = $swatchAttributeType;
    }

    /**
     * Set default facet size to 0 for swatches attributes before adding it to collection.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Attribute $subject Layer Attribute Filter
     * @param array                                                   $config  Filter default config
     *
     * @return array|null the config parameter that will be passed to the addFacetToCollection method.
     */
    public function beforeAddFacetToCollection(
        \Smile\ElasticsuiteCatalog\Model\Layer\Filter\Attribute $subject,
        $config = []
    ) {
        if ($this->swatchAttributeType->isSwatchAttribute($subject->getAttributeModel())) {
            $config['size'] = 0;
        }

        return [$config];
    }
}
