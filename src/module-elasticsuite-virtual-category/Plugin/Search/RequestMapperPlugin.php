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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Search;

use Smile\ElasticsuiteCore\Model\Search\RequestMapper;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;

/**
 * Extenstion of the category form UI data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
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
     * Constructor.
     *
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository Category repository.
     */
    public function __construct(\Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
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
     */
    public function afterGetFilters(
        RequestMapper $subject,
        $result,
        ContainerConfigurationInterface $containerConfiguration,
        SearchCriteriaInterface $searchCriteria
    ) {
        if ($this->isEnabled($containerConfiguration) && isset($result['category.category_id'])) {
            $categoryId = current(array_values($result['category.category_id']));
            $storeId    = $containerConfiguration->getStoreId();

            $category = $this->categoryRepository->get($categoryId, $storeId);
            $result[] = $category->getVirtualRule()->getCategorySearchQuery($category);
            unset($result['category.category_id']);
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
}
