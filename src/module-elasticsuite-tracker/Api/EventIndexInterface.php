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

namespace Smile\ElasticsuiteTracker\Api;

use Composer\EventDispatcher\Event;

/**
 * Tracker event log index.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface EventIndexInterface
{
    /**
     * @var string
     */
    const INDEX_IDENTIFIER = 'tracking_log_event';

    /**
     * Index a single event.
     *
     * @param array $event Event
     *
     * @return void
     */
    public function indexEvent($event);

    /**
     * Index a multiple events.
     *
     * @param array $events Events
     *
     * @return void
     */
    public function indexEvents($events);
}
