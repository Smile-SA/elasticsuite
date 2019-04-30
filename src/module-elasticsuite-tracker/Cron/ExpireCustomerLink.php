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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Cron;

/**
 * Cronjob that will anonymize log events that have reached their "delete_after" date if the anonymization is enabled.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ExpireCustomerLink
{
    /**
     * @var \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface
     */
    private $trackingService;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $helper;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $trackingService Tracking service.
     * @param \Smile\ElasticsuiteTracker\Helper\Data                          $helper          Tracking helper.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Api\CustomerTrackingServiceInterface $trackingService,
        \Smile\ElasticsuiteTracker\Helper\Data $helper
    ) {
        $this->trackingService = $trackingService;
        $this->helper          = $helper;
    }

    /**
     * Perform the cleaning of the expired entries if necessary and if anonymization is enabled.
     */
    public function execute()
    {
        if ($this->helper->isAnonymizationEnabled()) {
            $this->trackingService->deleteExpired();
        }
    }
}
