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
 * ElasticSuite request span_near query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-near-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanNear implements SpanQueryInterface
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
     * @var array
     */
    private $clauses = [];

    /**
     * @var integer
     */
    private int $slop;

    /**
     * @var boolean
     */
    private bool $inOrder;

    /**
     * The SpanNear query produce an Elasticsearch span_near query.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param array       $clauses Span clauses
     * @param int         $slop    Maximum number of intervening unmatched positions
     * @param bool        $inOrder Whether matches are required to be in-order
     * @param string|null $name    Query name
     * @param string      $boost   Query boost
     */
    public function __construct(
        array  $clauses = [],
        int    $slop = 12,
        bool   $inOrder = true,
        ?string $name = null,
        string $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->clauses = $clauses;
        $this->slop    = $slop;
        $this->inOrder = $inOrder;
        $this->name    = $name;
        $this->boost   = $boost;
    }

    /**
     * @return array
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * @return int
     */
    public function getSlop(): int
    {
        return $this->slop;
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     *
     * @return bool
     */
    public function getInOrder(): bool
    {
        return $this->inOrder;
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
        return SpanQueryInterface::TYPE_SPAN_NEAR;
    }
}
