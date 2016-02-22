<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Match implements QueryInterface
{
    const DEFAULT_MINIMUM_SHOULD_MATCH = "100%";

    private $name;

    private $boost;

    private $queryText;

    private $field;

    private $minimumShouldMatch;

    public function __construct(
        $name,
        $queryText,
        $field,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name = $name;
        $this->queryText = $queryText;
        $this->field = $field;
        $this->minimumShouldMatch = $minimumShouldMatch;
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
        return QueryInterface::TYPE_MATCH;
    }

    public function getQueryText()
    {
        return $this->queryText;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }
}