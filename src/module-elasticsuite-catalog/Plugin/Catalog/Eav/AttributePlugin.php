<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Eav;

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;

/**
 * Catalog EAV Attribute plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributePlugin
{
    /**
     * @var string[]
     */
    private $cleanCacheFields = [
        EavAttributeInterface::IS_FILTERABLE,
        EavAttributeInterface::IS_FILTERABLE_IN_SEARCH,
        EavAttributeInterface::IS_SEARCHABLE,
        EavAttributeInterface::IS_USED_FOR_PROMO_RULES,
        EavAttributeInterface::USED_FOR_SORT_BY,
        EavAttributeInterface::IS_VISIBLE_IN_ADVANCED_SEARCH,
        'search_weight',
    ];

    /**
     * @var array
     */
    private $updateMappingFields = [
        EavAttributeInterface::IS_FILTERABLE,
        EavAttributeInterface::IS_FILTERABLE_IN_SEARCH,
        EavAttributeInterface::IS_SEARCHABLE,
        EavAttributeInterface::IS_USED_FOR_PROMO_RULES,
        EavAttributeInterface::USED_FOR_SORT_BY,
        EavAttributeInterface::IS_VISIBLE_IN_ADVANCED_SEARCH,
    ];

    /**
     * @var \Smile\ElasticsuiteCore\Index\Indices\Config
     */
    private $indicesConfig;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexOperation;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * AttributePlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig   Indices config.
     * @param IndexerRegistry                              $indexerRegistry Indexer registry.
     * @param StoreManagerInterface                        $storeManager    Store Manager.
     * @param IndexOperationInterface                      $indexOperation  Index Operation.
     * @param ManagerInterface                             $messageManager  Message Manager.
     * @param LoggerInterface                              $logger          Logger.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig,
        IndexerRegistry $indexerRegistry,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexOperation,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->indicesConfig   = $indicesConfig;
        $this->indexerRegistry = $indexerRegistry;
        $this->storeManager    = $storeManager;
        $this->indexOperation  = $indexOperation;
        $this->messageManager  = $messageManager;
        $this->logger          = $logger;
    }

    /**
     * Invalidate indices config after attribute is modified.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject The attribute being saved
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $result  The attribute being saved
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function afterSave(
        \Magento\Catalog\Api\Data\ProductAttributeInterface $subject,
        \Magento\Catalog\Api\Data\ProductAttributeInterface $result
    ) {
        $cleanCache      = $this->shouldCleanCache($subject);
        $updateMapping   = $this->shouldUpdateMapping($subject);
        $invalidateIndex = false;

        if ($subject->dataHasChangedFor(EavAttributeInterface::USED_FOR_SORT_BY)) {
            // Other fields (is_searchable, is_filterable, is_filterable_in_search) are already managed by parent.
            $invalidateIndex = true;
        }

        if ($cleanCache || $updateMapping) {
            $this->indicesConfig->reset();
        }

        if ($updateMapping) {
            try {
                $stores = $this->storeManager->getStores();
                foreach ($stores as $store) {
                    $this->indexOperation->refreshMapping('catalog_product', $store);
                }
                // @codingStandardsIgnoreStart
                $this->messageManager->addNoticeMessage(__('Elasticsuite mapping has been updated real-time. However, your modifications might not be visible until the Catalogsearch Fulltext index is completely rebuilt.'));
                // @codingStandardsIgnoreEnd
            } catch (\Exception $exception) {
                // @codingStandardsIgnoreStart
                $this->messageManager->addErrorMessage(__('Elasticsuite mapping could not be updated real-time. Please wait for the complete reindexing of Catalogsearch Fulltext index.'));
                // @codingStandardsIgnoreEnd
                $this->logger->error($exception);
            }
        }

        if ($invalidateIndex) {
            $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();
            $this->messageManager->addNoticeMessage(__('Catalogsearch fulltext index has been invalidated.'));
        }

        return $result;
    }

    /**
     * Check if mapping should be updated
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute being saved
     *
     * @return bool
     */
    private function shouldUpdateMapping($subject)
    {
        $updateMapping = false;

        foreach ($this->updateMappingFields as $field) {
            if ($subject->dataHasChangedFor($field)) {
                $updateMapping = true;
                break;
            }
        }

        return $updateMapping;
    }

    /**
     * Check if cache should be invalidated
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute being saved
     *
     * @return bool
     */
    private function shouldCleanCache($subject)
    {
        $cleanCache = false;

        foreach ($this->cleanCacheFields as $field) {
            if ($subject->dataHasChangedFor($field)) {
                $cleanCache = true;
                break;
            }
        }

        return $cleanCache;
    }
}
