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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Request\Query;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Build ElasticSearch queries from search request QueryInterface queries.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BuilderInterface
{
    /**
     * Build the ES query from a Query
     *
     * @param QueryInterface $query Query to be built.
     *
     * @return array
     */
    public function buildQuery(QueryInterface $query);
}
