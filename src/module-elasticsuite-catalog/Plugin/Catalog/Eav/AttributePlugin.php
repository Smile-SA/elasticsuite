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
use Smile\ElasticsuiteCatalog\Helper\ProductAttribute as AttributeHelper;
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
     * @var array
     */
    private $updateMappingFields = [
        'is_searchable',
        'is_filterable',
        'search_weight',
        'is_used_for_sort_by',
        'is_used_in_spellcheck',
        'include_zero_false_values',
        'disable_norms',
        'default_search_analyzer',
    ];

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
        'disable_norms',
        'is_spannable',
        'default_analyzer',
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
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $attributeHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var boolean
     */
    private $saveIsNew;

    /**
     * AttributePlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig   Indices config.
     * @param IndexerRegistry                              $indexerRegistry Indexer registry.
     * @param StoreManagerInterface                        $storeManager    Store Manager.
     * @param IndexOperationInterface                      $indexOperation  Index Operation.
     * @param AttributeHelper                              $attributeHelper Attribute Helper.
     * @param ManagerInterface                             $messageManager  Message Manager.
     * @param LoggerInterface                              $logger          Logger.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig,
        IndexerRegistry $indexerRegistry,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexOperation,
        AttributeHelper $attributeHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->indicesConfig   = $indicesConfig;
        $this->indexerRegistry = $indexerRegistry;
        $this->storeManager    = $storeManager;
        $this->indexOperation  = $indexOperation;
        $this->attributeHelper = $attributeHelper;
        $this->messageManager  = $messageManager;
        $this->logger          = $logger;
    }

    /**
     * Check if indexer invalidation is needed on attribute save (searchable flag change)
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute resource model
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        \Magento\Catalog\Api\Data\ProductAttributeInterface $subject
    ) {
        $this->saveIsNew = $subject->isObjectNew();
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
        list($cleanCache, $updateMapping, $invalidateIndex) = $this->checkUpdateNeeded($subject);

        if ($cleanCache || $updateMapping) {
            $this->indicesConfig->reset();
        }

        if ($updateMapping) {
            $this->updateMapping($subject);
        }

        if ($invalidateIndex) {
            $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();
            $this->messageManager->addNoticeMessage(__('Catalogsearch fulltext index has been invalidated.'));
        }

        return $result;
    }

    /**
     * Check if operations (clean cache, mapping update, invalide index) must be triggered for current attribute.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute being saved
     *
     * @return array
     */
    private function checkUpdateNeeded($subject)
    {
        $updateMapping = $cleanCache = $invalidateIndex = false;

        if ($this->saveIsNew === true) {
            return [true, true, true];
        }

        $origOptions = $this->getOriginalMappingFieldOptions($subject);
        $options     = $this->getMappingFieldOptions($subject);

        foreach ($this->updateMappingFields as $field) {
            $origValue = ($origOptions[$field] ?? false);
            $value     = ($options[$field] ?? false);

            if ($origValue !== $value) {
                if ($field === 'default_search_analyzer') {
                    $cleanCache      = true;
                    $updateMapping   = true;
                    $invalidateIndex = true;
                    continue;
                }

                if ($field === 'search_weight') {
                    // Search weight has changed. Cache needs to be cleaned.
                    $cleanCache = true;
                    if (((int) $origValue === 1) && ((int) $value > (int) $origValue)) {
                        // Search weight moved from 1 to more. Mapping will change, so data need to be reindexed.
                        $updateMapping   = true;
                        $invalidateIndex = true;
                    }
                    continue;
                }

                // Value for option has changed. Cache needs to be cleared.
                $cleanCache = true;
                // If option is disabled, we do nothing. Data will remain until next full reindex.
                if ((bool) ($options[$field] ?? false) === true) {
                    // Configuration for is_searchable, is_filterable, etc... has been enabled. Mapping needs to be updated.
                    $updateMapping   = true;
                    // Configuration for is_searchable, is_filterable, etc... has been enabled. Data need to be reindexed.
                    $invalidateIndex = true;
                }
            }
        }

        foreach ($this->cleanCacheFields as $field) {
            if ($subject->dataHasChangedFor($field)) {
                if (($field === 'is_used_for_promo_rules') && ((bool) $subject->getData($field) === true)) {
                    if ((($origOptions['is_searchable'] ?? false) === false) || (($origOptions['is_filterable'] ?? false) === false)) {
                        // If field was not searchable or filterable, update the mapping. No need if the field was already existing.
                        $updateMapping   = true;
                        $invalidateIndex = true;
                    }
                }
                $cleanCache = true;
            }
        }

        return [$cleanCache, $updateMapping, $invalidateIndex];
    }

    /**
     * Update Mapping for current attribute
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute being saved
     *
     * @return void
     */
    private function updateMapping($subject)
    {
        try {
            $fields = array_unique([$subject->getAttributeCode(), $this->attributeHelper->getFilterField($subject)]);
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $this->indexOperation->updateMapping('catalog_product', $store, $fields);
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

    /**
     * Get the original (prior edit) mapping field configuration from an attribute.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute The attribute
     *
     * @return array
     */
    private function getOriginalMappingFieldOptions(\Magento\Catalog\Api\Data\ProductAttributeInterface $attribute)
    {
        $origAttribute = clone $attribute;
        $origAttribute->setData($attribute->getOrigData());

        return $this->getMappingFieldOptions($origAttribute);
    }

    /**
     * Get a mapping field configuration from an attribute.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute The attribute
     *
     * @return array
     */
    private function getMappingFieldOptions(\Magento\Catalog\Api\Data\ProductAttributeInterface $attribute)
    {
        return $this->attributeHelper->getMappingFieldOptions($attribute);
    }
}
