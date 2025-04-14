<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Query negation definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Not implements QueryInterface
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
    private $query;

    /**
     * Constructor.
     * @param \Magento\Framework\Search\Request\QueryInterface|null $query Negated query.
     * @param string|null                                           $name  Query name.
     * @param integer                                               $boost Query boost.
     */
    public function __construct(
        ?\Magento\Framework\Search\Request\QueryInterface $query = null,
        ?string $name = null,
        int $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name      = $name;
        $this->boost     = $boost;
        $this->query     = $query;
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
        return QueryInterface::TYPE_NOT;
    }

    /**
     * Negated query.
     *
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }
}
