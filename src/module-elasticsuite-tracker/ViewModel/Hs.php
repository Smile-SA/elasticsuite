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

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Smile\ElasticsuiteTracker\Model\Condition\CanViewNotification as ElasticsuiteNotification;

/**
 * Control display of elasticsuite notification modal
 *
 * @SuppressWarnings(PHPMD.ShortClassName)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Hs implements ArgumentInterface
{
    /**
     * @var ElasticsuiteNotification
     */
    private $canViewNotificationElasticsuite;

    /**
     * Notification view model constructor.
     *
     * @param ElasticsuiteNotification $canViewNotificationElasticsuite Elasticsuite notification.
     */
    public function __construct(
        ElasticsuiteNotification $canViewNotificationElasticsuite
    ) {
        $this->canViewNotificationElasticsuite = $canViewNotificationElasticsuite;
    }

    /**
     * Determine if the elasticsuite popup is visible
     *
     * @return bool
     */
    public function isTelemetryVisible(): bool
    {
        return $this->canViewNotificationElasticsuite->isVisible(
            ['notification_code' => 'elasticsuite_hs']
        );
    }
}
