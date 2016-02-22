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

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder;

use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\BuilderInterface;

/**
 * Build an ES filtered query.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Filtered extends AbstractComplexBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        $searchQuery = [];

        if ($query->getFilter()) {
            $searchQuery['filter'] = $this->parentBuilder->buildQuery($query->getFilter());
        }

        if ($query->getQuery()) {
            $searchQuery['query'] = $this->parentBuilder->buildQuery($query->getQuery());
        }

        return ['filtered' => $searchQuery];
    }
}
