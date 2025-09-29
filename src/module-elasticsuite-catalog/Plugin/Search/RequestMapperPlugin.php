<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Search;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Smile\ElasticsuiteCatalog\Api\LayeredNavAttributeInterface;
use Smile\ElasticsuiteCatalog\Model\Attribute\LayeredNavAttributesProvider;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Model\Search\RequestMapper;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Apply catalog product settings to the search API request mapper.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
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
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Helper\Mapping
     */
    private $mappingHelper;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * @var LayeredNavAttributesProvider
     */
    protected $layeredNavAttributesProvider;

    /**
     * Constructor.
     *
     * @param \Magento\Customer\Model\Session                     $customerSession              Customer session.
     * @param \Magento\Store\Model\StoreManagerInterface          $storeManager                 Store manager.
     * @param \Smile\ElasticsuiteCore\Helper\Mapping              $mappingHelper                Mapping helper.
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext                Search context.
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface    $categoryRepository           Category Repository.
     * @param RequestFieldMapper                                  $requestFieldMapper           Search request field mapper.
     * @param LayeredNavAttributesProvider                        $layeredNavAttributesProvider Layered navigation Attributes Provider.
     * @param array                                               $productSearchContainers      Product Search containers.
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper,
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        RequestFieldMapper $requestFieldMapper,
        LayeredNavAttributesProvider $layeredNavAttributesProvider,
        $productSearchContainers = []
    ) {
        $this->customerSession           = $customerSession;
        $this->storeManager              = $storeManager;
        $this->mappingHelper             = $mappingHelper;
        $this->searchContext             = $searchContext;
        $this->categoryRepository        = $categoryRepository;
        $this->requestFieldMapper        = $requestFieldMapper;
        $this->layeredNavAttributesProvider = $layeredNavAttributesProvider;
        if (is_array($productSearchContainers) && !empty($productSearchContainers)) {
            $this->productSearchContainers = array_merge($productSearchContainers, $this->productSearchContainers);
        }
    }

    /**
     * Post process catalog sort orders.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param RequestMapper                   $subject                Request mapper.
     * @param array                           $result                 Original sort orders.
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return array[]
     */
    public function afterGetSortOrders(
        RequestMapper $subject,
        $result,
        ContainerConfigurationInterface $containerConfiguration,
        SearchCriteriaInterface $searchCriteria
    ) {
        if ($this->isEnabled($containerConfiguration)) {
            $result = $this->addDefaultSortOrders($result, $containerConfiguration);

            $sortOrders = [];
            foreach ($result as $sortField => $sortParams) {
                if ($sortField == 'price') {
                    $sortParams['nestedFilter'] = ['price.customer_group_id' => $this->customerSession->getCustomerGroupId()];
                } elseif ($sortField == 'position') {
                    $categoryId = $this->getCurrentCategoryId($containerConfiguration, $searchCriteria);
                    $sortParams['nestedFilter'] = ['category.category_id' => $categoryId];
                }

                $sortOrders[$this->getMappingField($containerConfiguration, $sortField)] = $sortParams;
            }

            $result = $sortOrders;
        }

        return $result;
    }

    /**
     * Post process catalog filters.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param RequestMapper                   $subject                Request mapper.
     * @param array                           $result                 Original filters.
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function afterGetFilters(
        RequestMapper $subject,
        $result,
        ContainerConfigurationInterface $containerConfiguration,
        SearchCriteriaInterface $searchCriteria
    ) {
        if ($this->isEnabled($containerConfiguration)) {
            $filters = [];

            foreach ($result as $fieldName => $filterValue) {
                $layeredNavAttribute = $this->layeredNavAttributesProvider->getLayeredNavAttribute($fieldName);
                if ($layeredNavAttribute instanceof LayeredNavAttributeInterface) {
                    $fieldName = $layeredNavAttribute->getFilterField();
                    // Use reset to remove graphql operator.
                    $filters[$fieldName] = $layeredNavAttribute->getFilterQuery(reset($filterValue));
                } else {
                    $fieldName = $this->getMappingField($containerConfiguration, $fieldName);
                    $filters[$fieldName] = $this->getFieldValue($containerConfiguration, $fieldName, $filterValue);
                }
            }

            $result = $filters;

            if ($containerConfiguration->getName() === 'catalog_view_container') {
                $this->updateSearchContext(
                    $containerConfiguration->getStoreId(),
                    $this->getCurrentCategoryId($containerConfiguration, $searchCriteria)
                );
            }
        }

        // Discard Category Permissions fields in all cases. They don't exist in the mapping and cannot be handled like this.
        unset($result['category_permissions_field']);
        unset($result['category_permissions_value']);

        return $result;
    }

    /**
     * Add default sort orders according to context.
     *
     * @param array                           $sortOrders             Original sort orders.
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     *
     * @return array
     */
    private function addDefaultSortOrders($sortOrders, $containerConfiguration)
    {
        if ($containerConfiguration->getName() == "catalog_view_container" && empty($sortOrders)) {
            $sortOrders['position'] = ['direction' => SortOrderInterface::SORT_ASC];
        }

        if ($containerConfiguration->getName() == "quick_search_container" && empty($sortOrders)) {
            if ($searchQuery = $this->searchContext->getCurrentSearchQuery()) {
                if ($searchQuery->getId()) {
                    $sortOrders['search_query.position'] = [
                        'direction'     => SortOrderInterface::SORT_ASC,
                        'nestedFilter'  => ['search_query.query_id' => $searchQuery->getId()],
                    ];
                }
            }
        }

        return $sortOrders;
    }

    /**
     * Name of the field in the search engine mapping.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param string                          $fieldName              Request field name.
     *
     * @return string
     */
    private function getMappingField(ContainerConfigurationInterface $containerConfiguration, $fieldName)
    {
        $fieldName = $this->requestFieldMapper->getMappedFieldName($fieldName);

        try {
            $field = $containerConfiguration->getMapping()->getField($fieldName);
        } catch (\Exception $e) {
            $field = null;
        }

        try {
            if ($field === null || $field->getType() != 'boolean') {
                $optionTextFieldName = $this->mappingHelper->getOptionTextFieldName($fieldName);
                $containerConfiguration->getMapping()->getField($optionTextFieldName);
                $fieldName = $optionTextFieldName;
            }
        } catch (\Exception $e) {
            ;
        }

        return $fieldName;
    }

    /**
     * Get field value in the proper type.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param string                          $fieldName              Field name.
     * @param mixed                           $fieldValue             Field value.
     *
     * @return mixed
     */
    private function getFieldValue(ContainerConfigurationInterface $containerConfiguration, string $fieldName, $fieldValue)
    {
        try {
            $field = $containerConfiguration->getMapping()->getField($fieldName);
            if ($field->getType() === 'boolean' && is_array($fieldValue)) {
                foreach ($fieldValue as &$value) {
                    $value = (bool) $value;
                }
            }
        } catch (\Exception $e) {
            ;
        }

        return $fieldValue;
    }

    /**
     * Return current category id for the search request.
     *
     * @param ContainerConfigurationInterface $containerConfiguration Container configuration.
     * @param SearchCriteriaInterface         $searchCriteria         Search criteria.
     *
     * @return integer
     */
    private function getCurrentCategoryId(ContainerConfigurationInterface $containerConfiguration, SearchCriteriaInterface $searchCriteria)
    {
        if ($this->searchContext->getCurrentCategory() && $this->searchContext->getCurrentCategory()->getId()) {
            return $this->searchContext->getCurrentCategory()->getId();
        }

        $store      = $this->storeManager->getStore($containerConfiguration->getStoreId());
        $categoryId = $this->storeManager->getGroup($store->getStoreGroupId())->getRootCategoryId();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == "category_ids") {
                    $categoryId = $filter->getValue();
                }
            }
        }

        return $categoryId;
    }

    /**
     * Update the search context using current store id and category Id.
     *
     * @param integer $storeId    Store id.
     * @param integer $categoryId Category Id.
     *
     * @return void
     */
    private function updateSearchContext($storeId, $categoryId)
    {
        $this->searchContext->setStoreId($storeId);
        $category = $this->categoryRepository->get($categoryId, $storeId);

        if ($category->getId()) {
            $this->searchContext->setCurrentCategory($category);
        }
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
