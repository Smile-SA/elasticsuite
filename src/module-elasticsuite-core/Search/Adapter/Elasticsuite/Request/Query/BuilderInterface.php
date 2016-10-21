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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Build Elasticsearch queries from search request QueryInterface queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
