<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Terms extends Term
{
    public function __construct($name, $values, $field, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        if (is_string($values)) {
            $values = explode(',', $values);
        }

        parent::__construct($name, $values, $field, $boost);
    }


    public function getType()
    {
        return QueryInterface::TYPE_TERMS;
    }

    public function getValues()
    {
        return $this->getValue();
    }
}