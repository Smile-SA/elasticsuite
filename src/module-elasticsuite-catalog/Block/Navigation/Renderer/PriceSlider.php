<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Navigation\Renderer;

use Smile\ElasticsuiteCatalog\Model\Layer\Filter\Price;

/**
 * This block handle price slider rendering.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class PriceSlider extends Slider
{
    /**
     * The Data role, used for Javascript mapping of slider Widget
     *
     * @var string
     */
    protected $dataRole = "range-price-slider";

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        return $this->getFilter() instanceof Price;
    }

    /**
     * @return array
     */
    protected function getFieldFormat()
    {
        return $this->localeFormat->getPriceFormat();
    }
}
