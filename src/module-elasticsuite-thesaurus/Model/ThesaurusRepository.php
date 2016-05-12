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
namespace Smile\ElasticSuiteThesaurus\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusSearchResultsInterfaceFactory;
use Smile\ElasticSuiteThesaurus\Api\ThesaurusRepositoryInterface;

/**
 * Thesaurus Repository Object
 *
 * @category Smile
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ThesaurusRepository implements ThesaurusRepositoryInterface
{
    /**
     * Thesaurus Factory
     *
     * @var ThesaurusFactory
     */
    private $thesaurusFactory;

    /**
     * Search Result Factory
     *
     * @var ThesaurusSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * Magento Filter Builder
     *
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * Search Criteria Builder
     *
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * repository cache for thesaurus, by ids
     *
     * @var \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface[]
     */
    private $thesaurusRepositoryById = [];

    /**
     * PHP Constructor
     *
     * @param ThesaurusFactory                       $thesaurusFactory      Thesaurus Factory
     * @param ThesaurusSearchResultsInterfaceFactory $searchResultsFactory  Search Result Factory
     * @param FilterBuilder                          $filterBuilder         Filter Builder
     * @param SearchCriteriaBuilder                  $searchCriteriaBuilder Search Criteria Builder
     *
     * @return ThesaurusRepository
     */
    public function __construct(
        ThesaurusFactory $thesaurusFactory,
        ThesaurusSearchResultsInterfaceFactory $searchResultsFactory,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->thesaurusFactory = $thesaurusFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve a thesaurus by its ID
     *
     * @param int $thesaurusId id of the thesaurus
     *
     * @return \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($thesaurusId)
    {
        if (!isset($this->thesaurusRepositoryById[$thesaurusId])) {

            /** @var ThesaurusInterface $thesaurus */
            $thesaurus = $this->thesaurusFactory->create()->load($thesaurusId);
            if (!$thesaurus->getThesaurusId()) {
                $exception = new NoSuchEntityException();
                throw $exception->singleField('thesaurusId', $thesaurusId);
            }

            $this->thesaurusRepositoryById[$thesaurusId] = $thesaurus;
        }

        return $this->thesaurusRepositoryById[$thesaurusId];
    }

    /**
     * Save a thesaurus
     *
     * @param \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @return \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(\Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $this->validate($thesaurus);

        $thesaurus->save();

        $this->thesaurusRepositoryById[$thesaurus->getThesaurusId()] = $thesaurus;

        return $thesaurus;
    }

    /**
     * Delete a thesaurus
     *
     * @param \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return void
     */
    public function delete(\Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $thesaurusId = $thesaurus->getThesaurusId();

        $thesaurus->delete();

        if (isset($this->thesaurusRepositoryById[$thesaurusId])) {
            unset($this->thesaurusRepositoryById[$thesaurusId]);
        }
    }

    /**
     * Validate thesaurus values
     *
     * @param \Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus the thesaurus to validate
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    protected function validate(\Smile\ElasticSuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $exception = new \Magento\Framework\Exception\InputException();

        $validator = new \Zend_Validate();
        if (!$validator->is(trim($thesaurus->getName()), 'NotEmpty')) {
            $exception->addError(__(InputException::REQUIRED_FIELD, ['fieldName' => 'name']));
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }
}
