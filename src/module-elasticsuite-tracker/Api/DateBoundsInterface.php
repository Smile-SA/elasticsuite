<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Api;

/**
 * Tracker event log index.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Botis <botis@smile.fr>
 */
interface DateBoundsInterface
{
    /**
     * Get indices date bounds.
     *
     * @return array
     */
    public function getIndicesDateBounds(): array;
}
