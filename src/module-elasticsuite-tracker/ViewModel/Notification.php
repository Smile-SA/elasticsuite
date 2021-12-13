<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\ViewModel;

use Magento\AdminAnalytics\Model\Condition\CanViewNotification as AdminAnalyticsNotification;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\ReleaseNotification\Model\Condition\CanViewNotification as ReleaseNotification;
use Smile\ElasticsuiteTracker\Model\Condition\CanViewNotification as ElasticsuiteNotification;

/**
 * Control display of elasticsuite, admin analytics and release notification modals
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Notification implements ArgumentInterface
{
    /**
     * @var ElasticsuiteNotification
     */
    private $canViewNotificationElasticsuite;

    /**
     * @var AdminAnalyticsNotification
     */
    private $canViewNotificationAnalytics;

    /**
     * @var ReleaseNotification
     */
    private $canViewNotificationRelease;

    /**
     * Notification view model constructor.
     *
     * @param ElasticsuiteNotification   $canViewNotificationElasticsuite Elasticsuite notification.
     * @param AdminAnalyticsNotification $canViewNotificationAnalytics    Analytics notification.
     * @param ReleaseNotification        $canViewNotificationRelease      Release notification.
     */
    public function __construct(
        ElasticsuiteNotification $canViewNotificationElasticsuite,
        AdminAnalyticsNotification $canViewNotificationAnalytics,
        ReleaseNotification $canViewNotificationRelease
    ) {
        $this->canViewNotificationElasticsuite = $canViewNotificationElasticsuite;
        $this->canViewNotificationAnalytics = $canViewNotificationAnalytics;
        $this->canViewNotificationRelease = $canViewNotificationRelease;
    }

    /**
     * Determine if the elasticsuite popup is visible
     *
     * @return bool
     */
    public function isTelemetryVisible(): bool
    {
        return $this->canViewNotificationElasticsuite->isVisible(
            ['notification_code' => 'elasticsuite_telemetry']
        );
    }

    /**
     * Determine if the analytics popup is visible
     *
     * @return bool
     */
    public function isAnalyticsVisible(): bool
    {
        return $this->canViewNotificationAnalytics->isVisible([]);
    }

    /**
     * Determine if the release popup is visible
     *
     * @return bool
     */
    public function isReleaseVisible(): bool
    {
        return $this->canViewNotificationRelease->isVisible([]);
    }
}
