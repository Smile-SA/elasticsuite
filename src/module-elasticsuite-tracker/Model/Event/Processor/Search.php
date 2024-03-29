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
 * Process search query logged events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Search implements EventProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process($eventData)
    {
        if (isset($eventData['page']['search']['is_spellchecked'])) {
            $eventData['page']['search']['is_spellchecked'] = (bool) $eventData['page']['search']['is_spellchecked'];
        }

        // The query_id is an "integer" in the XML mapping, but if the index already exists, it will guess the mapping.
        // So we need to cast as integer for the "first insert on an already existing index".
        // Otherwise it would be mapped as a string and might cause failures when aggregating on this field.
        if (isset($eventData['page']['search']['query_id'])) {
            $eventData['page']['search']['query_id'] = (int) $eventData['page']['search']['query_id'];
        }

        return $eventData;
    }
}
