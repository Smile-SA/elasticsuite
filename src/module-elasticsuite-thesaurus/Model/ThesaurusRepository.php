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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\NotEmpty;
use Magento\Framework\Validator\ValidatorChain;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusSearchResultsInterfaceFactory;
use Smile\ElasticsuiteThesaurus\Api\ThesaurusRepositoryInterface;

/**
 * Thesaurus Repository Object
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
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
     * @var \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface[]
     */
    private $thesaurusRepositoryById = [];

    /**
     * PHP Constructor
     *
     * @param ThesaurusFactory                       $thesaurusFactory      Thesaurus Factory
     * @param ThesaurusSearchResultsInterfaceFactory $searchResultsFactory  Search Result Factory
     * @param FilterBuilder                          $filterBuilder         Filter Builder
     * @param SearchCriteriaBuilder                  $searchCriteriaBuilder Search Criteria Builder
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
     * @SuppressWarnings(PHPMD.StaticAccess) Mandatory to throw the exception via static access to be compliant
     * with Magento Extension Quality Program
     *
     * @param int $thesaurusId id of the thesaurus
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($thesaurusId)
    {
        if (!isset($this->thesaurusRepositoryById[$thesaurusId])) {
            /** @var ThesaurusInterface $thesaurus */
            $thesaurus = $this->thesaurusFactory->create()->load($thesaurusId);
            if (!$thesaurus->getThesaurusId()) {
                throw NoSuchEntityException::singleField('thesaurusId', $thesaurusId);
            }

            $this->thesaurusRepositoryById[$thesaurusId] = $thesaurus;
        }

        return $this->thesaurusRepositoryById[$thesaurusId];
    }

    /**
     * Save a thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $this->validate($thesaurus);

        $thesaurus->save();

        $this->thesaurusRepositoryById[$thesaurus->getThesaurusId()] = $thesaurus;

        return $thesaurus;
    }

    /**
     * Delete a thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     */
    public function delete(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $thesaurusId = $thesaurus->getThesaurusId();

        $thesaurus->delete();

        if (isset($this->thesaurusRepositoryById[$thesaurusId])) {
            unset($this->thesaurusRepositoryById[$thesaurusId]);
        }

        return $thesaurus;
    }

    /**
     * Enable a thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function enable(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $thesaurus->setIsActive(true);
        $thesaurus->save();

        return $thesaurus;
    }

    /**
     * Disable a thesaurus
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus Thesaurus data
     *
     * @return \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function disable(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $thesaurus->setIsActive(false);
        $thesaurus->save();

        return $thesaurus;
    }

    /**
     * Validate thesaurus values
     *
     * @param \Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus the thesaurus to validate
     *
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    protected function validate(\Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface $thesaurus)
    {
        $exception = new \Magento\Framework\Exception\InputException();

        if (!ValidatorChain::is(trim($thesaurus->getName()), NotEmpty::class)) {
            $exception->addError(__('"%fieldName" is required. Enter and try again.', ['fieldName' => 'name']));
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }
}
