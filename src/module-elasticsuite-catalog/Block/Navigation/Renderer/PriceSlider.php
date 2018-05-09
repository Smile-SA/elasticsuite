<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Navigation\Renderer;

use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCatalog\Model\Layer\Filter\Price;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Price as PriceDataProvider;

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

    /**
     * {@inheritDoc}
     */
    protected function getConfig()
    {
        $config = parent::getConfig();

        if ($this->isManualCalculation() && ($this->getStepValue() > 0)) {
            $config['step'] = $this->getStepValue();
        }

        if ($this->getFilter()->getCurrencyRate()) {
            $config['rate'] = $this->getFilter()->getCurrencyRate();
        }

        return $config;
    }

    /**
     * Returns min value of the slider.
     *
     * @return int
     */
    protected function getMinValue()
    {
        $minValue = $this->getFilter()->getMinValue();

        if ($this->isManualCalculation() && ($this->getStepValue() > 0)) {
            $stepValue = $this->getStepValue();
            $minValue  = floor($minValue / $stepValue) * $stepValue;
        }

        return $minValue;
    }

    /**
     * Returns max value of the slider.
     *
     * @return int
     */
    protected function getMaxValue()
    {
        $maxValue = $this->getFilter()->getMaxValue() + 1;

        if ($this->isManualCalculation() && ($this->getStepValue() > 0)) {
            $stepValue = $this->getStepValue();
            $maxValue  = ceil($maxValue / $stepValue) * $stepValue;
        }

        return $maxValue;
    }

    /**
     * Check if price interval is manually set in the configuration
     *
     * @return bool
     */
    private function isManualCalculation()
    {
        $result      = false;
        $calculation = $this->_scopeConfig->getValue(PriceDataProvider::XML_PATH_RANGE_CALCULATION, ScopeInterface::SCOPE_STORE);

        if ($calculation === PriceDataProvider::RANGE_CALCULATION_MANUAL) {
            $result = true;
        }

        return $result;
    }

    /**
     * Retrieve the value for "Default Price Navigation Step".
     *
     * @return int
     */
    private function getStepValue()
    {
        $value = $this->_scopeConfig->getValue(PriceDataProvider::XML_PATH_RANGE_STEP, ScopeInterface::SCOPE_STORE);

        return (int) $value;
    }
}
