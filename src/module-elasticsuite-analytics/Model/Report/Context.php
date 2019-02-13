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
        $dateRange = [
            'from' => $this->createDateFromText($this->request->getParam('from', 'today - 7days')),
            'to'   => $this->createDateFromText($this->request->getParam('to', 'today')),
        ];

        return $this->sortDateRange($dateRange);
    }

    /**
     * Convert and format string date to format expected by component.
     * TODO ribay@smile.fr clarify
     *
     * @param string $text Date as text.
     *
     * @return string
     */
    private function createDateFromText($text)
    {
        return (new \DateTime($text))->format('Y-m-d');
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
