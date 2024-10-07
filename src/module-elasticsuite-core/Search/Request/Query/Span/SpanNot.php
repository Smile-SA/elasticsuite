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
 * ElasticSuite request span_not query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-not-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanNot implements SpanQueryInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $include;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $exclude;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * The SpanNot query produce an Elasticsearch span_not query.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface $include Span Query of the "include" clause
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface $exclude Span Query of the "exclude" clause
     * @param string                                                          $name    Query Name
     * @param int                                                             $boost   Query Boost
     */
    public function __construct(
        SpanQueryInterface $include,
        SpanQueryInterface $exclude,
        string             $name = null,
        int                $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->include = $include;
        $this->exclude = $exclude;
        $this->name    = $name;
        $this->boost   = $boost;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getInclude(): SpanQueryInterface
    {
        return $this->include;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getExclude(): SpanQueryInterface
    {
        return $this->exclude;
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
        return SpanQueryInterface::TYPE_SPAN_NOT;
    }
}
