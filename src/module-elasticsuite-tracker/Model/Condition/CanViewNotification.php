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

namespace Smile\ElasticsuiteTracker\Model\Condition;

use Magento\Framework\View\Layout\Condition\VisibilityConditionInterface;
use Magento\Framework\App\CacheInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\Viewer\Logger;

/**
 * Dynamic validator for UI admin analytics notification, control UI component visibility.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class CanViewNotification implements VisibilityConditionInterface
{
    /**
     * Unique condition name.
     *
     * @var string
     */
    private static $conditionName = 'can_view_elasticsuite_notification';

    /**
     * Prefix for cache
     *
     * @var string
     */
    private static $cachePrefix = 'elasticsuite-notification';

    /**
     * @var Logger
     */
    private $viewerLogger;

    /**
     * @var CacheInterface
     */
    private $cacheStorage;

    /**
     * CanViewNotification constructor.
     *
     * @param Logger         $viewerLogger Logger Resource.
     * @param CacheInterface $cacheStorage Cache.
     */
    public function __construct(
        Logger $viewerLogger,
        CacheInterface $cacheStorage
    ) {
        $this->viewerLogger = $viewerLogger;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function isVisible(array $arguments): bool
    {
        $notificationCode = $arguments['notification_code'] ?? null;
        $cacheKey = self::$cachePrefix . $notificationCode;
        $value = $this->cacheStorage->load($cacheKey);
        if ($value !== 'log-exists') {
            $logExists = $this->viewerLogger->checkLogExists($notificationCode);
            if ($logExists) {
                $this->cacheStorage->save('log-exists', $cacheKey);
            }

            return !$logExists;
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
