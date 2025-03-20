<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Category Permissions data source.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryPermissions implements DatasourceInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index|null|false
     */
    private $categoryPermissionsIndex;

    /**
     * @var null|boolean
     */
    private $isEnabled = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager Object Manager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     */
    public function addData($storeId, array $indexData)
    {
        if ($this->isEnabled() && $this->getPermissionsIndex() !== false) {
            $permissionData = $this->getPermissionsIndex()->getIndexForProduct(array_keys($indexData), null, $storeId);

            foreach ($permissionData as $permission) {
                $indexData[(int) $permission['product_id']]['category_permissions'][] = [
                    'customer_group_id' => (int) $permission['customer_group_id'],
                    'permission'        => (int) $permission['grant_catalog_category_view'],
                ];
            }
        }

        return $indexData ?? [];
    }

    /**
     * Fetch CategoryPermissions resource model, if the class exist.
     *
     * @return false|\Magento\CatalogPermissions\Model\ResourceModel\Permission\Index
     */
    private function getPermissionsIndex()
    {
        if (null === $this->categoryPermissionsIndex) {
            $this->categoryPermissionsIndex = false;
            try {
                // Class will be missing if not using Adobe Commerce.
                $this->categoryPermissionsIndex = $this->objectManager->get(
                    \Magento\CatalogPermissions\Model\ResourceModel\Permission\Index::class
                );
            } catch (\Exception $exception) {
                ; // Nothing to do, it's already kinda hacky to allow this to happen.
            }
        }

        return $this->categoryPermissionsIndex;
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
