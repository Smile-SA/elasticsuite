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
 * ElasticSuite request span_multi query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-multi-term-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanMultiTerm implements SpanQueryInterface
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
    private $match;

    /**
     * The SpanMultiTerm query produce an Elasticsearch span_multi query.
     *
     * @param QueryInterface $match Query.
     * @param string         $name  Name of the query.
     * @param integer        $boost Query boost.
     */
    public function __construct(QueryInterface $match, $name = null, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->match = $match;
        $this->name  = $name;
        $this->boost = $boost;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getMatch(): QueryInterface
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
        return SpanQueryInterface::TYPE_SPAN_MULTI_TERM;
    }
}
