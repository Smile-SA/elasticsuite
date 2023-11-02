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
namespace Smile\ElasticsuiteTracker\Cron;

/**
 * Cronjob that will cleanup tracking data older than a configured delay.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CleanTrackingData
{
    /**
     * @var \Smile\ElasticsuiteTracker\Model\IndexManager
     */
    private $indexManager;

    /**
     * @var \Smile\ElasticsuiteTracker\Helper\Data
     */
    private $helper;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteTracker\Model\IndexManager $indexManager Index Manager.
     * @param \Smile\ElasticsuiteTracker\Helper\Data        $helper       Tracking Helper.
     */
    public function __construct(
        \Smile\ElasticsuiteTracker\Model\IndexManager $indexManager,
        \Smile\ElasticsuiteTracker\Helper\Data $helper
    ) {
        $this->indexManager = $indexManager;
        $this->helper       = $helper;
    }

    /**
     * Perform the cleaning of the old tracking data indices.
     */
    public function execute()
    {
        $this->indexManager->keepLatest((int) $this->helper->getRetentionDelay());
    }
}
