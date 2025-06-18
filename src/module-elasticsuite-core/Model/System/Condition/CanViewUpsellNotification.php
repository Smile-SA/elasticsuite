<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\System\Condition;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\View\Layout\Condition\VisibilityConditionInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\Viewer\Logger;
use Smile\ElasticsuiteCore\Model\ProductMetadata;

/**
 * ElasticSuite Notification
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
final class CanViewUpsellNotification implements VisibilityConditionInterface
{
    /**
     * Prefix for cache
     *
     * @var string
     */
    public const CACHE_PREFIX = 'elasticsuite-notification-';

    /**
     * Unique condition name.
     *
     * @var string
     */
    private static $conditionName = 'can_view_elasticsuite_upsell_notification';

    /**
     * @var Cache
     */
    private $cacheStorage;

    /**
     * @var \Smile\ElasticsuiteCore\Model\ProductMetadata
     */
    private $productMetadata;

    /**
     * CanViewUpsellNotification constructor.
     *
     * @param Cache           $cacheStorage    Cache.
     * @param ProductMetadata $productMetadata Product Metadata
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Helper\Cache $cacheStorage,
        \Smile\ElasticsuiteCore\Model\ProductMetadata $productMetadata
    ) {
        $this->cacheStorage    = $cacheStorage;
        $this->productMetadata = $productMetadata;
    }

    /**
     * {@inheritDoc}
     */
    final public function isVisible(array $arguments): bool
    {
        if ($this->productMetadata->getEdition() === ProductMetadata::EDITION_NAME) {
            $notificationCode = $arguments['notification_code'] ?? null;
            $cacheKey         = self::CACHE_PREFIX . $notificationCode;
            $value            = $this->cacheStorage->loadCache($cacheKey);

            if ($value !== 'log-exists') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get condition name
     *
     * @return string
     */
    public function getName(): string
    {
        return self::$conditionName;
    }
}
