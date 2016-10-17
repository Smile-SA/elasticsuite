<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Elastic suite request terms query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Terms extends Term
{
    /**
     * The term query produce an Elasticsearch terms query.
     *
     * @param string|array $values Search values. String are exploded using the comma as separator.
     * @param string       $field  Search field.
     * @param string       $name   Name of the query.
     * @param integer      $boost  Query boost.
     */
    public function __construct($values, $field, $name = null, $boost = QueryInterface::DEFAULT_BOOST_VALUE)
    {
        if (!is_array($values)) {
            $values = explode(',', $values);
        }

        parent::__construct($values, $field, $name, $boost);
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
