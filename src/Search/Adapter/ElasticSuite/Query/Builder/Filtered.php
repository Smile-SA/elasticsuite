<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder;

use Magento\Framework\Search\Request\QueryInterface;

class Filtered extends AbstractBuilder
{
    public function buildQuery(QueryInterface $query)
    {
        $searchQuery = [];

        if ($query->getFilter()) {
            $searchQuery['filter'] = $this->builder->buildQuery($query->getFilter());
        }

        if ($query->getQuery()) {
            $searchQuery['query'] = $this->builder->buildQuery($query->getQuery());
        }

        return ['filtered' => $searchQuery];
    }
}