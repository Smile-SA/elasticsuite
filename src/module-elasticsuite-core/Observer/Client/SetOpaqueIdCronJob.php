<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Observer\Client;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteCore\Api\Client\OpaqueIdProviderInterface;

/**
 * Make the X-Opaque-Id provider aware of the cron job about to run.
 * There is no "job end" event, but the jobs of a cron process run one after the other,
 * so each job replaces the previous one.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class SetOpaqueIdCronJob implements ObserverInterface
{
    /**
     * @var string
     */
    const JOB_NAME_PREFIX = 'cron/';

    /**
     * @var OpaqueIdProviderInterface
     */
    private $opaqueIdProvider;

    /**
     * Constructor.
     *
     * @param OpaqueIdProviderInterface $opaqueIdProvider X-Opaque-Id provider.
     */
    public function __construct(OpaqueIdProviderInterface $opaqueIdProvider)
    {
        $this->opaqueIdProvider = $opaqueIdProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(Observer $observer)
    {
        // Job name is "cron/<group>/<job code>", e.g. "cron/index/indexer_reindex_all_invalid".
        $jobName = (string) $observer->getEvent()->getData('job_name');
        if (str_starts_with($jobName, self::JOB_NAME_PREFIX)) {
            $jobName = substr($jobName, strlen(self::JOB_NAME_PREFIX));
        }

        $this->opaqueIdProvider->setCronJob($jobName !== '' ? $jobName : null);
    }
}
