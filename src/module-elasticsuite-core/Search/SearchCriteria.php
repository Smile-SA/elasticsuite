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
namespace Smile\ElasticsuiteCore\Search;

use Smile\ElasticsuiteCore\Api\Search\SearchCriteriaInterface;

/**
 * Search Criteria implementation for Search API
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchCriteria extends \Magento\Framework\Api\Search\SearchCriteria implements SearchCriteriaInterface
{
    const TRACK_TOTAL_HITS = 'track_total_hits';

    /**
     * {@inheritdoc}
     */
    public function getTrackTotalHits()
    {
        return $this->_get(self::TRACK_TOTAL_HITS);
    }

    /**
     * {@inheritdoc}
     */
    public function setTrackTotalHits($requestName)
    {
        return $this->setData(self::TRACK_TOTAL_HITS, $requestName);
    }
}
