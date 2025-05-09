<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Search;

use Magento\Framework\GraphQl\Query\Uid;
use Smile\ElasticsuiteCore\Model\Search\RequestMapper;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Extenstion of the category form UI data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RequestMapperPlugin
{
    /**
     * @var array
     */
    private $productSearchContainers = [
        'quick_search_container',
        'catalog_view_container',
    ];

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider
     */
    private $filterProvider;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /** @var Uid */
    private $uidEncoder;

    /**
     * Constructor.
     *
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface                  $categoryRepository Category repository.
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider     Category Filter provider.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory         $queryFactory       Query Factory.
     * @param Uid                                                               $uidEncoder         Encoder Uid.
     */
    public function __construct(
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider $filterProvider,
        QueryFactory $queryFactory,
        Uid $uidEncoder
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->queryFactory       = $queryFactory;
        $this->filterProvider     = $filterProvider;
        $this->uidEncoder         = $uidEncoder;
    }

    /**
     * Post process catalog filters (virtual categories handling).
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param RequestMapper                   $subject                Request mapper.
     * @param array                           $result                 Original filters.
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return array[]
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    public function afterGetFilters(
        RequestMapper $subject,
        $result,
        ContainerConfigurationInterface $containerConfiguration,
        SearchCriteriaInterface $searchCriteria
    ) {
        $storeId  = $containerConfiguration->getStoreId();
        if ($this->isEnabled($containerConfiguration)) {
            $result = $this->decodeCategoryUid($result);
            if (isset($result['category.category_id']) && !isset($result['category.category_uid'])) {
                $result[] = $this->getCategoriesQuery($result['category.category_id'], $storeId);

                unset($result['category.category_id']);
            } elseif (isset($result['category.category_uid']) && !isset($result['category.category_id'])) {
                $result[] = $this->getCategoriesQuery($result['category.category_uid'], $storeId);

                unset($result['category.category_uid']);
            } elseif (isset($result['category.category_id']) && isset($result['category.category_uid'])) {
                $result[] = $this->getCategoriesQuery($result['category.category_id'], $storeId);

                unset($result['category.category_id']);
                unset($result['category.category_uid']);
            }
        }

        return $result;
    }

    /**
     * Indicates if the plugin should be used or not.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return boolean
     */
    private function isEnabled(ContainerConfigurationInterface $containerConfiguration)
    {
        return in_array($containerConfiguration->getName(), $this->productSearchContainers);
    }

    /**
     * Get search query for a given category Id
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array $categoriesFilter The category filters
     * @param int   $storeId          The store Id
     *
     * @return QueryInterface|null
     */
    private function getCategoriesQuery($categoriesFilter, $storeId)
    {
        $result = [];

        foreach ($categoriesFilter as $operator => $categoryIds) {
            if (!is_array($categoryIds)) {
                $categoryIds = [$categoryIds];
            }

            $queries = [];
            foreach ($categoryIds as $categoryId) {
                $queries[] = $this->getCategorySubQuery($categoryId, $storeId);
            }

            if ($operator === 'in') {
                $result[] = $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['should' => $queries]);
            } else {
                $result += $queries;
            }
        }

        return $this->queryFactory->create(QueryInterface::TYPE_BOOL, ['must' => $result]);
    }

    /**
     * Get search query for a given category Id
     *
     * @param int $categoryId The category Id
     * @param int $storeId    The store Id
     *
     * @return QueryInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCategorySubQuery($categoryId, $storeId)
    {
        $category = $this->categoryRepository->get($categoryId, $storeId);

        return $this->filterProvider->getQueryFilter($category);
    }

    /**
     * Decode category_uid params if present in the request, because they are base64 encoded.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param array $result The array containing potentially the params.
     *
     * @return array
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlInputException
     */
    private function decodeCategoryUid(&$result)
    {
        if (isset($result['category.category_uid'])) {
            $decodeCb = function (string $categoryUid) {
                return $this->uidEncoder->decode($categoryUid);
            };

            foreach ($result['category.category_uid'] as $operator => &$categoryIds) {
                if (!is_array($categoryIds)) {
                    $categoryIds = [$categoryIds];
                }

                $categoryIds = array_map($decodeCb, $categoryIds);
            }
        }

        return $result;
    }
}
