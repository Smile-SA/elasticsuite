<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Condition;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder;

/**
 * Disable SQL Builder and create elasticsearch query from condition.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class ElasticsearchBuilder extends Builder
{
    /**
     * Attach conditions filter to collection
     *
     * @param AbstractCollection $collection Product Collection.
     * @param Combine            $combine    Conditions.
     * @return void
     */
    public function attachConditionToCollection(
        AbstractCollection $collection,
        Combine $combine
    ): void {
        $query = $combine->getSearchQuery();
        $collection->addQueryFilter($query);
    }
}
