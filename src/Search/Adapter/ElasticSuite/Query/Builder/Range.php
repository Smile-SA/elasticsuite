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

class Range extends AbstractBuilder
{
    public function buildQuery(QueryInterface $query)
    {
        return ['range' => [$query->getField() => ['from' => $query->getFrom(), 'to' => $query->getTo()]]];
    }
}