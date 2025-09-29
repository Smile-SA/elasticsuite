<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Helper;

/**
 * Data helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Maximum number of search terms to display in reports configuration path
     * @var string
     */
    const CONFIG_MAX_SEARCH_TERMS_XPATH = 'smile_elasticsuite_analytics/search_terms/max_size';

    /**
     * Maximum number of hours a valid pending event can stay in the events queue before a warning is displayed.
     * @var string
     */
    const CONFIG_MAX_HOURS_BEFORE_EVENTS_WARNING = 'smile_elasticsuite_analytics/pending_events/warn_after_hours';

    /**
     * Returns the maximum number of search terms to display in reports
     *
     * @return int
     */
    public function getMaxSearchTerms()
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_MAX_SEARCH_TERMS_XPATH);
    }

    /**
     * Returns the maximum number of hours an event can stay in the events queue before a warning is displayed.
     *
     * @return int
     */
    public function getMaxHoursBeforeEventsWarning()
    {
        return (int) $this->scopeConfig->getValue(self::CONFIG_MAX_HOURS_BEFORE_EVENTS_WARNING);
    }
}
