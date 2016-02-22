<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Term implements QueryInterface
{
    private $name;

    private $boost;

    private $value;

    private $field;

    public function __construct($name, $value, $field, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name = $name;
        $this->value = $value;
        $this->field = $field;
        $this->boost = $boost;
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
        return QueryInterface::TYPE_TERM;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getField()
    {
        return $this->field;
    }
}