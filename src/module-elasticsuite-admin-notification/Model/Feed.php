<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAdminNotification
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAdminNotification\Model;

/**
 * Elasticsuite Admin Notification feed.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAdminNotification
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Feed extends \Magento\AdminNotification\Model\Feed
{
    /**
     * URL of the Elasticsuite RSS feed.
     */
    const ELASTICSUITE_FEED_URL = 'elasticsuite.io/notifications.rss';

    /**
     * Cache key for the Elasticsuite notification check.
     */
    const ELASTICSUITE_FEED_UPDATE_CACHE_KEY = 'elasticsuite_admin_notifications_lastcheck';

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if ($this->_feedUrl === null) {
            $httpPath = $this->_backendConfig->isSetFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://';
            $this->_feedUrl = $httpPath . self::ELASTICSUITE_FEED_URL;
        }

        return $this->_feedUrl;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load(self::ELASTICSUITE_FEED_UPDATE_CACHE_KEY);
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), self::ELASTICSUITE_FEED_UPDATE_CACHE_KEY);

        return $this;
    }
}
