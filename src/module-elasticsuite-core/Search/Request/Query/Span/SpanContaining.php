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
 * ElasticSuite request span_containing query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/query-dsl-span-containing-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanContaining implements SpanQueryInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $big;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $little;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var integer
     */
    private $boost;

    /**
     * The SpanContaining query produce an Elasticsearch span_containing query.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface $big    Span Query of the "big" clause
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface $little Span Query of the "little" clause
     * @param string                                                          $name   Query Name
     * @param int                                                             $boost  Query Boost
     */
    public function __construct(
        SpanQueryInterface $big,
        SpanQueryInterface $little,
        ?string            $name = null,
        int                $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->big    = $big;
        $this->little = $little;
        $this->name   = $name;
        $this->boost  = $boost;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getBig(): SpanQueryInterface
    {
        return $this->big;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getLittle(): SpanQueryInterface
    {
        return $this->little;
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
        return SpanQueryInterface::TYPE_SPAN_CONTAINING;
    }
}
