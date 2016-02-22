<?php

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

class Nested implements QueryInterface
{
    const SCORE_MODE_AVG  = 'avg';
    const SCORE_MODE_SUM  = 'sum';
    const SCORE_MODE_MIN  = 'min';
    const SCORE_MODE_MAX  = 'max';
    const SCORE_MODE_NONE = 'none';

    /**
     * @var string
     */
    private $scoreMode;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * @var string
     */
    private $path;

    /**
     * @var QueryInterface
     */
    private $query;

    public function __construct(
        $name,
        $path,
        \Magento\Framework\Search\Request\QueryInterface $query = null,
        $scoreMode= self::SCORE_MODE_NONE,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name = $name;
        $this->boost = $boost;
        $this->path = $path;
        $this->scoreMode = $scoreMode;
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

    public function getPath()
    {
        return $this->path;
    }

    public function getScoreMode()
    {
        return $this->scoreMode;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getType()
    {
        return QueryInterface::TYPE_NESTED;
    }
}