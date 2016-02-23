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
 * Elastic suite request terms query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Terms extends Term
{
    /**
     * The term query produce an ElasticSearch terms query.
     *
     * @param string       $name   Name of the query.
     * @param string|array $values Search values. String are exploded using the comma as separator.
     * @param string       $field  Search field.
     * @param integer      $boost  Query boost.
     */
    public function __construct($name, $values, $field, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        if (!is_array($values)) {
            $values = explode(',', $values);
        }

        parent::__construct($name, $values, $field, $boost);
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return QueryInterface::TYPE_TERMS;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->getValue();
    }
}
