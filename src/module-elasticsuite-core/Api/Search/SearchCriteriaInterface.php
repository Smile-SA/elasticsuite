<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Search;

/**
 * Search Criteria implementation for Search API
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface SearchCriteriaInterface extends \Magento\Framework\Api\Search\SearchCriteriaInterface
{
    /**
     * @return int|bool
     */
    public function getTrackTotalHits();

    /**
     * If the number of total hits should be tracked.
     * This can be an integer or a boolean :
     *  - int : the maximum number of hits that will be tracked.
     *  - boolean "false" : total hits will not be tracked.
     *  - boolean "true" : total hits will be tracked and the exact total will be returned.
     *
     * @param int|bool $trackTotalHits Number of hits to track, or boolean.
     *
     * @return $this
     */
    public function setTrackTotalHits($trackTotalHits);
}
