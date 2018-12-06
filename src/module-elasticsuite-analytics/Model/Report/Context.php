<?php

namespace Smile\ElasticsuiteAnalytics\Model\Report;

class Context
{
    private $request;


    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    public function getStoreId()
    {
        return $this->request->getParam('store');
    }

    public function getDateRange()
    {
        $dateRange = [
            'from' => $this->createDateFromText($this->request->getParam('from', 'today - 7days')),
            'to'   => $this->createDateFromText($this->request->getParam('to', 'today')),
        ];

        return $this->sortDateRange($dateRange);
    }

    private function createDateFromText($text)
    {
        return (new \DateTime($text))->format('Y-m-d');
    }

    private function sortDateRange($dateRange)
    {
        if ($dateRange['from'] > $dateRange['to']) {
            $dateRange = ['from' => $dateRange['to'], 'to' => $dateRange['from']];
        }

        return $dateRange;
    }
}