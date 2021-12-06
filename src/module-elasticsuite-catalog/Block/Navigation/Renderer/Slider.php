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
     * @var array
     */
    protected $intervals;

    /**
     * @var boolean
     */
    protected $showAdaptiveSlider;

    /**
     * @var array
     */
    protected $adaptiveIntervals;

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
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function showAdaptiveSlider(): bool
    {
        if (null === $this->showAdaptiveSlider) {
            $this->showAdaptiveSlider = false;
            if ($this->catalogSliderHelper->isAdaptiveSliderEnabled()
                && ($this->getFilter()->getItemsCount() >= CatalogSliderHelper::ADAPTIVE_MINIMUM_ITEMS)
            ) {
                $hasDispersedData = false;
                try {
                    $layer = $this->getFilter()->getLayer();
                    $attributeModel = $this->getFilter()->getAttributeModel();
                    if ($layer && $attributeModel) {
                        $facetName = $this->catalogSliderHelper->getStatsAggregation($attributeModel->getAttributeCode());
                        $stats = $layer->getProductCollection()->getFacetedData($facetName);
                        $stats = current($stats);
                        /* Coefficient of Variation */
                        $cv = ($stats['std_deviation'] ?? 0) / ($stats['avg'] ?? 1);
                        $hasDispersedData = ($cv > 1.0);
                        $lowerStdDevBound = $stats['std_deviation_bounds']['lower'] ?? 0;
                        $upperStdDevBound = $stats['std_deviation_bounds']['upper'] ?? 0;
                        if ($lowerStdDevBound && $upperStdDevBound) {
                            $hasDispersedData = (
                                $hasDispersedData || (
                                    ($this->getMinValue() < $lowerStdDevBound)
                                    || ($this->getMaxValue() > $upperStdDevBound)
                                )
                            );
                        }
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    ;
                }

                $this->showAdaptiveSlider = $hasDispersedData;
            }
        }

        return $this->showAdaptiveSlider;
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
        if (null === $this->intervals) {
            $intervals = [];
            foreach ($this->getFilter()->getItems() as $item) {
                $intervals[] = ['value' => $item->getValue(), 'count' => $item->getCount()];
            }
            $this->intervals = $intervals;
        }

        return $this->intervals;
    }

    /**
     * Return available adaptive intervals.
     *
     * @return array
     */
    private function getAdaptiveIntervals(): array
    {
        if (null === $this->adaptiveIntervals) {
            $this->adaptiveIntervals = [];
            if ($this->showAdaptiveSlider()) {
                $this->adaptiveIntervals = $this->prepareAdaptiveIntervals();
            }
        }

        return $this->adaptiveIntervals;
    }

    /**
     * Prepare adaptive intervals.
     *
     * @return array
     */
    private function prepareAdaptiveIntervals(): array
    {
        $adaptiveIntervals = [];
        $intervals = $this->getIntervals();

        $totalCount = array_sum(array_column($intervals, 'count'));
        // Cumulative Distribution Function Value.
        $cdfValue = 0;
        $keys = [];
        $keyValues = [];
        foreach ($intervals as $interval) {
            // We use cumulative distribution function to create the adaptive intervals.
            $value = ($interval['count'] / $totalCount) * 100;
            $cdfValue += $value;
            $key = (int) floor($cdfValue);
            $keys[$key] = $key;
            $keyValues[$key] = ['key' => $key, 'value' => $interval['value']];
            $adaptiveIntervals[(string) $cdfValue] = [
                'originalValue' => $interval['value'],
                'value'         => $cdfValue,
                'count'         => $interval['count'],
            ];
        }

        if (!empty($adaptiveIntervals)) {
            $keys = array_values($keys);
            $length = count($keyValues);
            $missingSlots = [];
            for ($i = 0; $i < ($length - 1); $i++) {
                $left  = $keys[$i];
                $right = $keys[$i + 1];
                $cdfRange   = $keyValues[$right]['key'] - $keyValues[$left]['key'];
                $priceRange = $keyValues[$right]['value'] - $keyValues[$left]['value'];
                $priceStep  = $priceRange / $cdfRange;
                for ($j = 1; $j < $cdfRange; $j++) {
                    $cdfKey = $keyValues[$left]['key'] + $j;
                    $price  = $keyValues[$left]['value'] + ($priceStep * $j);
                    $missingSlots[(string) $cdfKey] = [
                        'originalValue' => $price,
                        'value'         => $cdfKey,
                        'count'         => 0,
                    ];
                }
            }

            $maxIntervalKey = (string) $cdfValue;
            $adaptiveIntervals[(string) 101] = [
                'originalValue' => $adaptiveIntervals[$maxIntervalKey]['originalValue'] + 1,
                'value' => 101,
                'count' => 0,
            ];
            // Fill up the intermediate step.
            $adaptiveIntervals = $adaptiveIntervals + $missingSlots;
            ksort($adaptiveIntervals);

            $adaptiveIntervals = array_values($adaptiveIntervals);
        }

        return $adaptiveIntervals;
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
