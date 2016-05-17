<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteThesaurus\Api\Data;

use \Magento\Framework\Api\SearchResultsInterface;

/**
 * Search Result Interface for Thesaurus
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ThesaurusSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get seller list.
     *
     * @return \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface[]
     */
    public function getItems();

    /**
     * Set seller list.
     *
     * @param \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface[] $items list of thesaurus
     *
     * @return \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusSearchResultsInterface
     */
    public function setItems(array $items);
}
