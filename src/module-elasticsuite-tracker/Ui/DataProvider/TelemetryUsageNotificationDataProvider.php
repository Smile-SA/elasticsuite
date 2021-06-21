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

namespace Smile\ElasticsuiteTracker\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;

/**
 * Data Provider for the Admin usage UI component.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class TelemetryUsageNotificationDataProvider extends AbstractDataProvider
{
    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Add field filter to collection
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Filter $filter Filter.
     * @return mixed
     */
    public function addFilter(Filter $filter)
    {
        return null;
    }
}
