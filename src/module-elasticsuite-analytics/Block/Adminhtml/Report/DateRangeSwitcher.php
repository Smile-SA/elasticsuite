<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Report;

/**
 * Block used to display date range switcher in reports.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DateRangeSwitcher extends \Magento\Backend\Block\Template
{

    protected $_template = 'Smile_ElasticsuiteAnalytics::report/date_range_switcher.phtml';

    private $reportContext;

    private $jsonSerializer;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Report\Context $reportContext,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->reportContext  = $reportContext;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function getMinDate()
    {
        return $this->formatDate($this->reportContext->getDateRange()['from'], \IntlDateFormatter::SHORT, false, 'UTC');
    }

    public function getMaxDate()
    {
        return $this->formatDate($this->reportContext->getDateRange()['to'], \IntlDateFormatter::SHORT, false, 'UTC');
    }

    public function getJsConfig()
    {
        $config = [
            'dateFormat' => $this->getDateFormat(),
            'from'       => ['id' => $this->getJsId('date-range-picker', 'from')],
            'to'         => ['id' => $this->getJsId('date-range-picker', 'to')],

        ];

        return $this->jsonSerializer->serialize($config);
    }

    private function getDateFormat()
    {
        return $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
    }
}