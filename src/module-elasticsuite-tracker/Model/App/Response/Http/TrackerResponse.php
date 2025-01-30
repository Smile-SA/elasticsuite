<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\App\Response\Http;

use Magento\Framework\App\PageCache\NotCacheableInterface;
use Magento\Framework\App\Response\Http;

/**
 * Custom Http Response object for the elasticsuite/tracker/hit legacy controller.
 * Using this object instead of a classic Http response object
 * - prevents \Magento\PageCache\Model\App\Response\HttpPlugin from being triggered in the first place
 * - and if it was triggered, makes sure that the sendVary method is not called
 *   - which would result in the loss of the Vary cookie if the user is logged in.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class TrackerResponse extends Http implements NotCacheableInterface
{
}
