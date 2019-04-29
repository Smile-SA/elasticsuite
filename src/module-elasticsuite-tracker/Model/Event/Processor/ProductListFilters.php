<?php
/**
 * DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
* versions in the future.
*
* @category  Smile
* @package   Smile\ElasticsuiteTracker
* @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
* @license   Open Software License ("OSL") v. 3.0
*/

namespace Smile\ElasticsuiteTracker\Model\Event\Processor;

use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Process product list filters in logged events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ProductListFilters implements EventProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if (isset($eventData['page']['product_list']['filters'])) {
            $filters = [];
            foreach ($eventData['page']['product_list']['filters'] as $filterName => $filterValues) {
                $filterValues = explode('|', $filterValues);
                foreach ($filterValues as $filterValue) {
                    $filters[] = ['name' => $filterName, 'value' => $filterValue];
                }
            }
            $eventData['page']['product_list']['filters'] = $filters;
        }

        return $eventData;
    }
}
