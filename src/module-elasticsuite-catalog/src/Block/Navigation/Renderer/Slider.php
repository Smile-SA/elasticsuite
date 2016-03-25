<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Block\Navigation\Renderer;

use Smile\ElasticSuiteCatalog\Model\Layer\Filter\Price;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Json\EncoderInterface;

/**
 * This block handle price slider rendering.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Slider extends AbstractRenderer
{
    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     *
     * @param Context          $context      Template context.
     * @param EncoderInterface $jsonEncoder  JSON Encoder.
     * @param FormatInterface  $localeFormat Price format config.
     * @param array            $data         Custom data.
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        FormatInterface $localeFormat,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->jsonEncoder  = $jsonEncoder;
        $this->localeFormat = $localeFormat;
    }

    /**
     * Return the config of the price slider JS widget.
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $config = [
            'minValue'         => $this->getMinValue(),
            'maxValue'         => $this->getMaxValue(),
            'currentValue'     => $this->getCurrentValue(),
            'priceFormat'      => $this->localeFormat->getPriceFormat(),
            'intervals'        => $this->getIntervals(),
            'urlTemplate'      => $this->getUrlTemplate(),
            'messageTemplates' => [
                'displayCount' => __('<%- count %> products'),
                'displayEmpty' => __('No products in the selected range.'),
            ],
        ];

        return $this->jsonEncoder->encode($config);
    }

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        return $this->getFilter() instanceof Price;
    }

    /**
     * Returns min value of the slider.
     *
     * @return int
     */
    private function getMinValue()
    {
        return $this->getFilter()->getMinValue();
    }

    /**
     * Returns max value of the slider.
     *
     * @return int
     */
    private function getMaxValue()
    {
        return $this->getFilter()->getMaxValue() + 1;
    }

    /**
     * Returns values currently selected by the user.
     *
     * @return array
     */
    private function getCurrentValue()
    {
        $currentValue = $this->getFilter()->getCurrentValue();

        if (!is_array($currentValue)) {
            $currentValue = [];
        }

        if (!isset($currentValue['from']) || $currentValue['from'] === '') {
            $currentValue['from'] = $this->getMinValue();
        }

        if (!isset($currentValue['to']) || $currentValue['to'] === '') {
            $currentValue['to'] = $this->getMaxValue();
        }

        return $currentValue;
    }

    /**
     * Return available intervals.
     *
     * @return array
     */
    private function getIntervals()
    {
        $intervals = [];

        foreach ($this->getFilter()->getItems() as $item) {
            $intervals[] = ['value' => $item->getValue(), 'count' => $item->getCount()];
        }

        return $intervals;
    }

    /**
     * Retrieve filter URL template with placeholders for range.
     *
     * @return string
     */
    private function getUrlTemplate()
    {
        $filter = $this->getFilter();
        $item   = current($this->getFilter()->getItems());

        $regexp      = "/({$filter->getRequestVar()})=([0-9]+)/";
        $replacement = '${1}=<%- from %>-<%- to %>';

        return preg_replace($regexp, $replacement, $item->getUrl());
    }
}
