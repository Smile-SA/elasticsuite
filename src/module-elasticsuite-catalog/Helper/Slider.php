<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;

/**
 * Slider class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Botis <botis@smile.fr>
 */
class Slider extends AbstractHelper
{
    /** @var string */
    const XML_PATH_ADAPTIVE_SLIDER_ENABLED = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/adaptive_slider_enabled';

    /**
     * Width (in standard deviation) to use for the std deviation bounds.
     *
     * @var int
     */
    const STD_DEVIATION_SIGMA = 3;

    /**
     * Minimum number of items to use the adaptive slider.
     * @var int
     */
    const ADAPTIVE_MINIMUM_ITEMS = 3;

    /**
     * Coefficient of variation (Std Deviation/Mean) above which enable adaptive slider
     * @var float
     */
    const COEFFICIENT_OF_VARIATION_THRESHOLD = 1.0;

    /**
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * Slider constructor.
     *
     * @param Context            $context            Helper context.
     * @param RequestFieldMapper $requestFieldMapper Request field mapper.
     */
    public function __construct(Context $context, RequestFieldMapper $requestFieldMapper)
    {
        parent::__construct($context);
        $this->requestFieldMapper = $requestFieldMapper;
    }

    /**
     * Is adaptive slider enabled ?
     *
     * @return bool
     */
    public function isAdaptiveSliderEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ADAPTIVE_SLIDER_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return the name of the aggregation containing the stats metrics to be used to determine
     * if using an adaptive slider is valid in the context.
     *
     * @param string $filterField Filter field.
     *
     * @return string
     */
    public function getStatsAggregation($filterField)
    {
        $filterField = $this->requestFieldMapper->getMappedFieldName($filterField);

        return sprintf("%s.stats", $filterField);
    }
}
