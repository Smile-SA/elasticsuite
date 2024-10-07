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
 * Nested queries definition implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
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

    /**
     *
     * @param string                                           $path      Nested path.
     * @param \Magento\Framework\Search\Request\QueryInterface $query     Nested query.
     * @param string                                           $scoreMode Score mode of the nested query..
     * @param string                                           $name      Query name.
     * @param integer                                          $boost     Query boost.
     */
    public function __construct(
        $path,
        \Magento\Framework\Search\Request\QueryInterface $query = null,
        $scoreMode = self::SCORE_MODE_NONE,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name      = $name;
        $this->boost     = $boost;
        $this->path      = $path;
        $this->scoreMode = $scoreMode;
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
        return QueryInterface::TYPE_NESTED;
    }

    /**
     * Nested query path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Nested query score mode.
     *
     * @return string
     */
    public function getScoreMode()
    {
        return $this->scoreMode;
    }

    /**
     * Nested query.
     *
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }
}
