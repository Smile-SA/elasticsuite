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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Processor;

use Smile\ElasticsuiteTracker\Api\EventProcessorInterface;

/**
 * Process order items logged events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class OrderItems implements EventProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if (isset($eventData['page']['order']['items'])) {
            $eventData['page']['order']['items'] = array_values($eventData['page']['order']['items']);
            $eventDate = $eventData['date'] ?? null;

            foreach ($eventData['page']['order']['items'] as &$item) {
                if (isset($item['category_ids'])) {
                    $item['category_ids'] = explode(',', $item['category_ids']);
                }
                if ($eventDate) {
                    $item['date'] = $eventDate;
                }
            }
        }

        return $eventData;
    }
}
