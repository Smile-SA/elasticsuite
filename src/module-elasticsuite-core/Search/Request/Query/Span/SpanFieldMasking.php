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
 * ElasticSuite request span_field_masking query.
 *
 * Documentation : @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-field-masking-query.html
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpanFieldMasking implements SpanQueryInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    private $query;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var integer|mixed
     */
    private $boost;

    /**
     * The SpanFieldMasking query produce an Elasticsearch span_field_masking query.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface $query Span Query
     * @param string                                                          $field Field
     * @param string|null                                                     $name  Query Name
     * @param                                                                 $boost Boost
     */
    public function __construct(
        SpanQueryInterface $query,
        string $field,
        ?string $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->query = $query;
        $this->field = $field;
        $this->name  = $name;
        $this->boost = $boost;
    }

    /**
     * @return \Smile\ElasticsuiteCore\Search\Request\Query\SpanQueryInterface
     */
    public function getQuery(): SpanQueryInterface
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
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
        return SpanQueryInterface::TYPE_SPAN_FIELD_MASKING;
    }
}
