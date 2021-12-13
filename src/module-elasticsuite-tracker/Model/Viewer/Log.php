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

namespace Smile\ElasticsuiteTracker\Model\Viewer;

use Magento\Framework\DataObject;

/**
 * Admin Analytics log resource
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Log extends DataObject
{
    /**
     * Get log id
     *
     * @return int
     */
    public function getId() : ?int
    {
        return $this->getData('id');
    }

    /**
     * Get notification code
     *
     * @return string
     */
    public function getNotificationCode() : ?string
    {
        return $this->getData('notification_code');
    }
}
