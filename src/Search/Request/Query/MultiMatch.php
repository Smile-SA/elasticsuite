<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Multi match search request query implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MultiMatch implements QueryInterface
{
    /**
     * @var string
     */
    const DEFAULT_MINIMUM_SHOULD_MATCH = "1";

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
    private $queryText;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $minimumShouldMatch;

    /**
     *
     * @param string  $queryText          Matched text.
     * @param array   $fields             Query fields as key with their weigth as values.
     * @param string  $minimumShouldMatch Minimum should match for the match query.
     * @param string  $name               Query name.
     * @param integer $boost              Query boost.
     */
    public function __construct(
        $queryText,
        array $fields,
        $minimumShouldMatch = self::DEFAULT_MINIMUM_SHOULD_MATCH,
        $name = null,
        $boost = QueryInterface::DEFAULT_BOOST_VALUE
    ) {
        $this->name               = $name;
        $this->queryText          = $queryText;
        $this->fields             = $fields;
        $this->minimumShouldMatch = $minimumShouldMatch;
        $this->boost              = $boost;
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
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_MULTIMATCH;
    }

    /**
     * Query match text.
     *
     * @return string
     */
    public function getQueryText()
    {
        return $this->queryText;
    }

    /**
     * Query fields (weighted).
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Minimum should match for the match query.
     *
     * @return string
     */
    public function getMinimumShouldMatch()
    {
        return $this->minimumShouldMatch;
    }
}
