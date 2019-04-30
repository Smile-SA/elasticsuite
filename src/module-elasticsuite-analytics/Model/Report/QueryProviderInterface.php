<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Report;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Query provider interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
interface QueryProviderInterface
{
    /**
     * Get query.
     *
     * @return QueryInterface
     */
    public function getQuery();
}
