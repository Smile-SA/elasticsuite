<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Product\Search\Request\Container\Filter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;

/**
 * Category Permissions filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryPermissions implements FilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\CategoryPermissions\Filter\Provider
     */
    private $categoryPermissionsFilter;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var null|boolean
     */
    private $isEnabled = null;

    /**
     * Search Blacklist filter constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory            $queryFactory              Query Factory
     * @param \Smile\ElasticsuiteCatalog\Model\CategoryPermissions\Filter\Provider $categoryPermissionsFilter Query Filter for Permissions
     * @param \Magento\Customer\Model\Session                                      $customerSession           Customer Session
     * @param \Magento\Framework\ObjectManagerInterface                            $objectManager             Object Manager
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCatalog\Model\CategoryPermissions\Filter\Provider $categoryPermissionsFilter,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->queryFactory              = $queryFactory;
        $this->categoryPermissionsFilter = $categoryPermissionsFilter;
        $this->customerSession           = $customerSession;
        $this->objectManager             = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterQuery()
    {
        $query = null;

        if ($this->isEnabled()) {
            $query = $this->categoryPermissionsFilter->getQueryFilter(
                $this->customerSession->getCustomerGroupId(),
                -2 // Cannot use \Magento\CatalogPermissions\Model::PERMISSION_DENY because the class can be missing.
            );
        }

        return $query;
    }

    /**
     * Check if category permissions feature is enabled.
     *
     * @return bool
     */
    private function isEnabled()
    {
        if (null === $this->isEnabled) {
            $this->isEnabled = false;
            try {
                // Class will be missing if not using Adobe Commerce.
                $config = $this->objectManager->get(\Magento\CatalogPermissions\App\ConfigInterface::class);
                $this->isEnabled = $config->isEnabled();
            } catch (\Exception $exception) {
                ; // Nothing to do, it's already kinda hacky to allow this to happen.
            }
        }

        return $this->isEnabled;
    }
}
