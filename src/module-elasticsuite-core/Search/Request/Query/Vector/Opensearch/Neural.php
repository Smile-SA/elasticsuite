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
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query\Vector\Opensearch;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * neural query implementation, for Opensearch.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Neural implements QueryInterface
{
    public const TYPE_NEURAL = 'neuralQuery';
    public const DEFAULT_EMBEDDING_FIELD = 'embedding';

    /**
     * @var string
     */
    private string $field;

    /**
     * @var string
     */
    private string $queryText;

    /**
     * @var integer
     */
    private int $kValue;

    /**
     * @var string|null
     */
    private ?string $name;

    /**
     * @var float|integer
     */
    private float|int $boost;

    /**
     * @var string|null
     */
    private ?string $modelId;

    /**
     * @param string      $queryText The query Text
     * @param int         $kValue    The K value
     * @param string      $field     The field containing embeddings
     * @param string|null $name      Query name
     * @param float       $boost     Boost value
     * @param string|null $modelId   Model Id
     */
    public function __construct(
        string $queryText,
        int $kValue,
        string $field = self::DEFAULT_EMBEDDING_FIELD,
        ?string $name = null,
        float $boost = QueryInterface::DEFAULT_BOOST_VALUE,
        ?string $modelId = null
    ) {
        $this->field     = $field;
        $this->queryText = $queryText;
        $this->kValue    = $kValue;
        $this->name      = $name;
        $this->modelId   = $modelId;
        $this->boost     = $boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return self::TYPE_NEURAL;
    }

    /**
     * Target field
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Query text.
     *
     * @return string
     */
    public function getQueryText(): string
    {
        return $this->queryText;
    }

    /**
     * K value.
     *
     * @return int
     */
    public function getKValue(): int
    {
        return $this->kValue;
    }

    /**
     * Get model id.
     *
     * @return string|null
     */
    public function getModelId(): ?string
    {
        return $this->modelId;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBoost(): float
    {
        return $this->boost;
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }
}
