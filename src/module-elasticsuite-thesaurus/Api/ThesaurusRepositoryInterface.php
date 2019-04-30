<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Api;

/**
 * Thesaurus Repository interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ThesaurusRepositoryInterface
{
    /**
     * Retrieve a thesaurus by its ID
     *
     * @param int $thesaurusId id of the thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($thesaurusId);

    /**
     * save a Thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus);

    /**
     * delete a Thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function delete(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus);
}
