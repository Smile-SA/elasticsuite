<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Filtered implements QueryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var QueryInterface
     */
    private $filter;

    /**
     * @var QueryInterface
     */
    private $query;

    public function __construct(
        $name,
        \Magento\Framework\Search\Request\QueryInterface $query = null,
        \Magento\Framework\Search\Request\QueryInterface $filter = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name = $name;
        $this->boost = $boost;
        $this->filter = $filter;
        $this->query = $query;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getBoost()
    {
        return $this->boost;
    }

    public function getType()
    {
        return QueryInterface::TYPE_FILTER;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getFilter()
    {
        return $this->query;
    }
}