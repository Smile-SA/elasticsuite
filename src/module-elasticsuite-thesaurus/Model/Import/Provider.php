<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model\Import;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;
use Smile\ElasticsuiteThesaurus\Api\ThesaurusRepositoryInterface;
use Smile\ElasticsuiteThesaurus\Model\ThesaurusFactory;
use Smile\ElasticsuiteThesaurus\Model\Thesaurus;

/**
 * Class Import Provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Provider
{
    /**
     * @var ThesaurusRepositoryInterface
     */
    protected $thesaurusRepository;

    /**
     * Thesaurus Factory
     *
     * @var ThesaurusFactory
     */
    protected $thesaurusFactory;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @param ThesaurusRepositoryInterface $thesaurusRepository Thesaurus repository.
     * @param ThesaurusFactory             $thesaurusFactory    Thesaurus factory.
     * @param StoreRepositoryInterface     $storeRepository     Store repository.
     */
    public function __construct(
        ThesaurusRepositoryInterface $thesaurusRepository,
        ThesaurusFactory $thesaurusFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->thesaurusRepository = $thesaurusRepository;
        $this->thesaurusFactory    = $thesaurusFactory;
        $this->storeRepository     = $storeRepository;
    }

    /**
     * Create Thesaurus
     *
     * @return Thesaurus
     */
    public function createThesaurus(): Thesaurus
    {
         return $this->thesaurusFactory->create();
    }

    /**
     * Save Thesaurus
     *
     * @param Thesaurus $model Thesaurus model.
     * @return ThesaurusInterface
     */
    public function saveThesaurus($model): ThesaurusInterface
    {
        return $this->thesaurusRepository->save($model);
    }

    /**
     * Remove Thesaurus
     *
     * @param Thesaurus $model Thesaurus model.
     * @return ThesaurusInterface
     */
    public function removeThesaurus($model): ThesaurusInterface
    {
        return $this->thesaurusRepository->delete($model);
    }

    /**
     * @param string $data Store data.
     * @return array
     */
    public function processStoresData($data): array
    {
        $storeIds = [];

        if (empty($data)) {
            $storeIds = [0];
        }
        if (!empty($data)) {
            foreach (explode(';', $data) as $storeItem) {
                if ($storeId = $this->getStoreId($storeItem)) {
                    $storeIds[] = $storeId;
                }
            }
        }

        return $storeIds;
    }

    /**
     * @param string $data Terms data.
     * @param string $type Thesaurus type.
     * @return array
     */
    public function processTermsData($data, $type): array
    {
        $termsRelations = [];
        if ($type === ThesaurusInterface::TYPE_SYNONYM) {
            foreach (array_filter(explode(';', $data)) as $termItem) {
                $termsRelations[] = [
                    'values'  => $termItem,
                ];
            }
        }
        if ($type === ThesaurusInterface::TYPE_EXPANSION) {
            foreach (array_filter(explode(';', $data)) as $termItem) {
                $expansionTerms = explode(':', $termItem);
                $termsRelations[] = [
                    'reference_term' => $expansionTerms[0],
                    'values'  => $expansionTerms[1],
                ];
            }
        }

        return $termsRelations;
    }

    /**
     * @param string $store Store key.
     * @return int|null
     */
    private function getStoreId($store): ?int
    {
        try {
            return $this->storeRepository->get($store)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
