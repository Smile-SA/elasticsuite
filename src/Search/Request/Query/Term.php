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
 * Elastic suite request term query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Term implements QueryInterface
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
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $field;

    /**
     * The term query produce an ElasticSearch term query.
     *
     * @param string  $name  Name of the query.
     * @param string  $value Search value.
     * @param string  $field Search field.
     * @param integer $boost Query boost.
     */
    public function __construct($name, $value, $field, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->field = $field;
        $this->boost = $boost;
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
        return QueryInterface::TYPE_TERM;
    }

    /**
     * Search value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Search field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }
}
