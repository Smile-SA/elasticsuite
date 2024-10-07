<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Api;

/**
 * Customer Tracking Service interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface CustomerTrackingServiceInterface
{
    /**
     * Add a tracked event.
     *
     * @param mixed $eventData Event data.
     *
     * @return void
     */
    public function hit($eventData): void;

    /**
     * Add a tracked event.
     *
     * @param array $eventData The event Data
     *
     * @return void
     */
    public function addEvent($eventData);

    /**
     * Anonymize customer data for a given customer Id and a delay.
     * If the delay is null, the anonymization process is run immediately.
     *
     * @param int            $customerId The customer Id.
     * @param \DateTime|null $delay      The date after which all the customer data must be cleared.
     *
     * @return void
     */
    public function anonymizeCustomerData(int $customerId, \DateTime $delay = null);

    /**
     * Process cleaning of all expired customer data.
     *
     * @return void
     */
    public function deleteExpired();

    /**
     * Retrieve all visitor ids matching a given customer
     *
     * @param int $customerId The customer id
     *
     * @return array
     */
    public function getVisitorIds(int $customerId);
}
