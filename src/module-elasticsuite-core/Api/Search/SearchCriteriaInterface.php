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
     * @param int|bool $trackTotalHits
     *
     * @return $this
     */
    public function setTrackTotalHits($trackTotalHits);
}
