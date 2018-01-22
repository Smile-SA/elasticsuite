<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Search;

use Smile\ElasticsuiteCore\Model\Search\RequestMapper;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;

/**
 * Apply catalog product settings to the search API request mapper.
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
     * @var array
     */
    private $fieldMapper = [
        'price'        => 'price.price',
        'position'     => 'category.position',
        'category_ids' => 'category.category_id',
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
     * Constructor.
     *
     * @param \Magento\Customer\Model\Session            $customerSession Customer session.
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager    Store manager.
     * @param \Smile\ElasticsuiteCore\Helper\Mapping     $mappingHelper   Mapping helper.
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Helper\Mapping $mappingHelper
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager    = $storeManager;
        $this->mappingHelper   = $mappingHelper;
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
                $fieldName = $this->getMappingField($containerConfiguration, $fieldName);
                $filters[$fieldName] = $filterValue;
            }

                $result = $filters;
        }

        return $result;
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
        if (isset($this->fieldMapper[$fieldName])) {
            $fieldName = $this->fieldMapper[$fieldName];
        }

        try {
            $optionTextFieldName = $this->mappingHelper->getOptionTextFieldName($fieldName);
            $containerConfiguration->getMapping()->getField($optionTextFieldName);
            $fieldName = $optionTextFieldName;
        } catch (\Exception $e) {
            ;
        }

        return $fieldName;
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
