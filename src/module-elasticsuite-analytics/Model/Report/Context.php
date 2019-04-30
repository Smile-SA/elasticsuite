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

namespace Smile\ElasticsuiteAnalytics\Model\Report;

/**
 * Report context model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Context
{
    /**
     * @var string
     */
    const DEFAULT_FROM_DATE = 'today - 7 days';

    /**
     * @var string
     */
    const DEFAULT_TO_DATE = 'today';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Context constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request App request.
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get store Id.
     *
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->request->getParam('store');
    }

    /**
     * Get date range.
     *
     * @return array
     */
    public function getDateRange()
    {
        $fromDate = $this->request->getParam('from', self::DEFAULT_FROM_DATE);
        if ($fromDate !== self::DEFAULT_FROM_DATE) {
            $fromDate = base64_decode($fromDate);
        }
        $toDate = $this->request->getParam('to', self::DEFAULT_TO_DATE);
        if ($toDate !== self::DEFAULT_TO_DATE) {
            $toDate = base64_decode($toDate);
        }

        $dateRange = [
            'from' => $this->createDateFromText($fromDate),
            'to'   => $this->createDateFromText($toDate),
        ];

        return $this->sortDateRange($dateRange);
    }

    /**
     * Convert a DateTime compatible date or expression into an ES compatible date format
     *
     * @param string $text Date as text.
     *
     * @return string
     */
    private function createDateFromText($text)
    {
        try {
            $date = new \DateTime($text);
        } catch (\Exception $e) {
            $date = new \DateTime(self::DEFAULT_TO_DATE);
        }

        return $date->format('Y-m-d');
    }

    /**
     * Arrange a given date range so it is valid.
     *
     * @param array $dateRange Date range.
     *
     * @return array
     */
    private function sortDateRange($dateRange)
    {
        if ($dateRange['from'] > $dateRange['to']) {
            $dateRange = ['from' => $dateRange['to'], 'to' => $dateRange['from']];
        }

        return $dateRange;
    }
}
