<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Navigation\Renderer;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\View\Element\Template\Context;
use Smile\ElasticsuiteCatalog\Helper\Slider as CatalogSliderHelper;
use Smile\ElasticsuiteCatalog\Model\Layer\Filter\Decimal;

/**
 * This block handle standard decimal slider rendering.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Slider extends AbstractRenderer
{
    /**
     * The Data role, used for Javascript mapping of slider Widget
     *
     * @var string
     */
    protected $dataRole = "range-slider";

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var FormatInterface
     */
    protected $localeFormat;

    /**
     * @var CatalogSliderHelper
     */
    protected $catalogSliderHelper;

    /**
     *
     * @param Context             $context             Template context.
     * @param CatalogHelper       $catalogHelper       Catalog helper.
     * @param EncoderInterface    $jsonEncoder         JSON Encoder.
     * @param FormatInterface     $localeFormat        Price format config.
     * @param CatalogSliderHelper $catalogSliderHelper Catalog slider helper.
     * @param array               $data                Custom data.
     */
    public function __construct(
        Context $context,
        CatalogHelper $catalogHelper,
        EncoderInterface $jsonEncoder,
        FormatInterface $localeFormat,
        CatalogSliderHelper $catalogSliderHelper,
        array $data = []
    ) {
        parent::__construct($context, $catalogHelper, $data);

        $this->jsonEncoder         = $jsonEncoder;
        $this->localeFormat        = $localeFormat;
        $this->catalogSliderHelper = $catalogSliderHelper;
    }

    /**
     * Return the config of the price slider JS widget.
     *
     * @return string
     */
    public function getJsonConfig()
    {
        $config = $this->getConfig();

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Retrieve the data role
     *
     * @return string
     */
    public function getDataRole()
    {
        $filter = $this->getFilter();

        return $this->dataRole . "-" . $filter->getRequestVar();
    }

    /**
     * Show adaptive slider ?
     *
     * @return bool
     */
    public function showAdaptiveSlider(): bool
    {
        return $this->catalogSliderHelper->isAdaptiveSliderEnabled();
    }

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        return $this->getFilter() instanceof Decimal;
    }

    /**
     * Retrieve Field Format for slider display
     *
     * @return array
     */
    protected function getFieldFormat()
    {
        $format = $this->localeFormat->getPriceFormat();

        $attribute = $this->getFilter()->getAttributeModel();

        $format['pattern']           = (string) $attribute->getDisplayPattern();
        $format['precision']         = (int) $attribute->getDisplayPrecision();
        $format['requiredPrecision'] = (int) $attribute->getDisplayPrecision();
        $format['integerRequired']   = (int) $attribute->getDisplayPrecision() > 0;

        return $format;
    }

    /**
     * Retrieve configuration
     *
     * @return array
     */
    protected function getConfig()
    {
        $config = [
            'minValue'           => $this->getMinValue(),
            'maxValue'           => $this->getMaxValue(),
            'currentValue'       => $this->getCurrentValue(),
            'fieldFormat'        => $this->getFieldFormat(),
            'intervals'          => $this->getIntervals(),
            'adaptiveIntervals'  => $this->getAdaptiveIntervals(),
            'showAdaptiveSlider' => $this->showAdaptiveSlider(),
            'urlTemplate'        => $this->getUrlTemplate(),
            'messageTemplates'   => [
                'displayOne'   => __('1 product'),
                'displayCount' => __('<%- count %> products'),
                'displayEmpty' => __('No products in the selected range.'),
            ],
        ];

        return $config;
    }

    /**
     * Returns min value of the slider.
     *
     * @return int
     */
    protected function getMinValue()
    {
        return $this->getFilter()->getMinValue();
    }

    /**
     * Returns max value of the slider.
     *
     * @return int
     */
    protected function getMaxValue()
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
     * Return available adaptive intervals.
     *
     * @return array
     */
    private function getAdaptiveIntervals(): array
    {
        $adaptiveInterval = [];
        if (!$this->showAdaptiveSlider()) {
            return $adaptiveInterval;
        }

        $intervals = $this->getIntervals();

        $totalCount = array_sum(array_column($intervals, 'count'));
        // Cumulative Distribution Function Value
        $cdfValue = 0;
        foreach ($intervals as $interval) {
            // We use cumulative distribution function to create the adaptive intervals.
            $value = ($interval['count'] / $totalCount) * 100;
            $cdfValue += $value;
            $adaptiveInterval[] = [
                'originalValue' => $interval['value'],
                'value'   => $cdfValue,
                'count' => $interval['count'],
            ];
        }

        if (!empty($adaptiveInterval)) {
            $maxIntervalKey = max(array_keys($adaptiveInterval));
            $adaptiveInterval[$maxIntervalKey]['originalValue'] = $adaptiveInterval[$maxIntervalKey]['originalValue'] + 1;
        }

        return $adaptiveInterval;
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

        $regexp      = "/({$filter->getRequestVar()})=(-?[0-9]+)/";
        $replacement = '${1}=<%- from %>-<%- to %>';

        return preg_replace($regexp, $replacement, $item->getUrl());
    }
}
