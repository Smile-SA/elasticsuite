<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Block\Adminhtml\Report;

/**
 * Block used to display date range switcher in reports.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DateRangeSwitcher extends \Magento\Backend\Block\Template
{
    /**
     * @var string
     */
    protected $_template = 'Smile_ElasticsuiteAnalytics::report/date_range_switcher.phtml';

    /**
     * @var \Smile\ElasticsuiteAnalytics\Model\Report\Context
     */
    private $reportContext;

    /**
     * @var \Smile\ElasticsuiteTracker\Model\IndexManager
     */
    private $indexManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * DateRangeSwitcher constructor.
     * @param \Magento\Backend\Block\Template\Context           $context        Context.
     * @param \Smile\ElasticsuiteAnalytics\Model\Report\Context $reportContext  Report context.
     * @param \Smile\ElasticsuiteTracker\Model\IndexManager     $indexManager   Index manager.
     * @param \Magento\Framework\Serialize\Serializer\Json      $jsonSerializer Json serializer/unserializer.
     * @param array                                             $data           Data.
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Smile\ElasticsuiteAnalytics\Model\Report\Context $reportContext,
        \Smile\ElasticsuiteTracker\Model\IndexManager $indexManager,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->reportContext  = $reportContext;
        $this->indexManager   = $indexManager;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get min/from date from context.
     *
     * @return string
     */
    public function getMinDate()
    {
        return $this->formatDate($this->reportContext->getDateRange()['from'], \IntlDateFormatter::SHORT, false, 'UTC');
    }

    /**
     * Get max/to date from context.
     *
     * @return string
     */
    public function getMaxDate()
    {
        return $this->formatDate($this->reportContext->getDateRange()['to'], \IntlDateFormatter::SHORT, false, 'UTC');
    }

    /**
     * Get JS config
     *
     * @return bool|string
     */
    public function getJsConfig()
    {
        $config = [
            'dateFormat' => $this->getDateFormat(),
            'from'       => ['id' => $this->getJsId('date-range-picker', 'from')],
            'to'         => ['id' => $this->getJsId('date-range-picker', 'to')],
        ];

        $dateBounds = $this->indexManager->getIndicesDateBounds(
            \Smile\ElasticsuiteTracker\Api\EventIndexInterface::INDEX_IDENTIFIER,
            $this->reportContext->getStoreId()
        );
        if (!empty($dateBounds)) {
            list($minDate, $maxDate) = $dateBounds;
            $config['minDate'] = $this->formatDate($minDate, \IntlDateFormatter::SHORT, false, 'UTC');
            $config['maxDate'] = $this->formatDate($maxDate, \IntlDateFormatter::SHORT, false, 'UTC');
        }

        return $this->jsonSerializer->serialize($config);
    }

    /**
     * Return the date format used by the JS date picker.
     *
     * @return string
     */
    private function getDateFormat()
    {
        return $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
    }
}
