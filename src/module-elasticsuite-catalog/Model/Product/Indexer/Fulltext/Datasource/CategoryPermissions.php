<?php

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalogOptimizerCustomerGroup\Ui\Component\Optimizer\Form\Modifier\CustomerGroups;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

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
        if ($this->getPermissionsIndex() !== false) {
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
}
