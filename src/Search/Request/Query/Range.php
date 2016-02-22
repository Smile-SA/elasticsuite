<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Range implements QueryInterface
{
    private $boost;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $field;

    /**
     * @var int
     */
    private $from;

    /**
     * @var int
     */
    private $to;

    public function __construct($name, $field, $from, $to, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->boost = $boost;
        $this->field = $field;
        $this->from  = $from;
        $this->to    = $to;
    }

    public function getBoost()
    {
        return $this->boost;
    }

    public function getType()
    {
        return QueryInterface::TYPE_RANGE;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo()
    {
        return $this->to;
    }
}