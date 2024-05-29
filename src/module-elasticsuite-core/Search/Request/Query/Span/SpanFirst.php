<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query\Span;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface;

/**
 * ElasticSuite request span_first query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-first-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanFirst implements SpanQueryInterface
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
     * @var integer
     */
    private $end;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $match;

    /**
     * The SpanFirst query produce an Elasticsearch span_first query.
     *
     * @param SpanQueryInterface $match Another span query to match.
     * @param int                $end   The maximum end position permitted in a match.
     * @param string             $name  Name of the query.
     * @param integer            $boost Query boost.
     */
    public function __construct(SpanQueryInterface $match, int $end, $name = null, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->match = $match;
        $this->end   = $end;
        $this->boost = $boost;
    }

    /**
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getMatch(): SpanQueryInterface
    {
        return $this->match;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return SpanQueryInterface::TYPE_SPAN_FIRST;
    }
}
