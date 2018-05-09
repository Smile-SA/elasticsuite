<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;

/**
 * This class provides a lot of util methods used by Eav indexer related resource models.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Indexer extends AbstractIndexer
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * Indexer constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection     $resource     Resource Connection
     * @param \Magento\Store\Model\StoreManagerInterface    $storeManager Store Manager
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool Metadata Pool
     */
    public function __construct(ResourceConnection $resource, StoreManagerInterface $storeManager, MetadataPool $metadataPool)
    {
        parent::__construct($resource, $storeManager);
        $this->metadataPool = $metadataPool;
    }

    /**
     * Retrieve store root category id.
     *
     * @param \Magento\Store\Api\Data\StoreInterface|int|string $store Store id.
     *
     * @return integer
     */
    protected function getRootCategoryId($store)
    {
        if (is_numeric($store) || is_string($store)) {
            $store = $this->getStore($store);
        }

        $storeGroupId = $store->getStoreGroupId();

        return $this->storeManager->getGroup($storeGroupId)->getRootCategoryId();
    }

    /**
     * Retrieve Metadata for an entity
     *
     * @param string $entityType The entity
     *
     * @return EntityMetadataInterface
     */
    protected function getEntityMetaData($entityType)
    {
        return $this->metadataPool->getMetadata($entityType);
    }
}
