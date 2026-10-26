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
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Attribute as AttributeResource;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCatalog\Helper\ProductAttribute as AttributeHelper;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;

/**
 * Catalog EAV Attribute plugin.
 *
 * Plugged on the attribute resource model rather than on the attribute model, so that attributes saved through
 * the product attribute repository (REST/SOAP API, imports) are handled too: they do not go through
 * AbstractModel::save().
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributePlugin
{
    /**
     * List of _mapping field properties_ for which we want to detect changes.
     * Sometimes the mapping field property name differs from the attribute configuration property name.
     * For instance attribute[search_analyzer] => field[default_search_analyzer]
     *         or attribute[scoring_algorithm] => field[similarity].
     *
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
        'similarity',
    ];

    /**
     * List of _attribute properties_ for which we went to detect changes.
     * Sometimes the attribute configuration property name differs from the mapping field property name.
     * For instance attribute[search_analyzer] => field[default_search_analyzer]
     *         or attribute[scoring_algorithm] => field[similarity].
     *
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
        'scoring_algorithm',
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
     * @var \Magento\Framework\App\State
     */
    private $appState;

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
     * @param State|null                                   $appState        Application state.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig,
        IndexerRegistry $indexerRegistry,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexOperation,
        AttributeHelper $attributeHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        ?State $appState = null
    ) {
        $this->indicesConfig   = $indicesConfig;
        $this->indexerRegistry = $indexerRegistry;
        $this->storeManager    = $storeManager;
        $this->indexOperation  = $indexOperation;
        $this->attributeHelper = $attributeHelper;
        $this->messageManager  = $messageManager;
        $this->logger          = $logger;
        $this->appState        = $appState ?: ObjectManager::getInstance()->get(State::class);
    }

    /**
     * Check if indexer invalidation is needed on attribute save (searchable flag change)
     *
     * @param AttributeResource $subject   Attribute resource model
     * @param AbstractModel     $attribute Attribute being saved
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(AttributeResource $subject, AbstractModel $attribute)
    {
        if (!($attribute instanceof ProductAttributeInterface) || !$this->isProductAttribute($attribute)) {
            return;
        }

        $this->saveIsNew = $attribute->isObjectNew();
    }

    /**
     * Invalidate indices config after attribute is modified.
     *
     * @param AttributeResource $subject   Attribute resource model
     * @param AttributeResource $result    Attribute resource model
     * @param AbstractModel     $attribute Attribute being saved
     *
     * @return AttributeResource
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(AttributeResource $subject, AttributeResource $result, AbstractModel $attribute)
    {
        if (!($attribute instanceof ProductAttributeInterface) || !$this->isProductAttribute($attribute)) {
            return $result;
        }

        list($cleanCache, $updateMapping, $invalidateIndex) = $this->checkUpdateNeeded($attribute);

        if ($cleanCache || $updateMapping) {
            $this->indicesConfig->reset();
        }

        if ($updateMapping) {
            $this->updateMapping($attribute);
        }

        if ($invalidateIndex) {
            $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();
            if ($this->isAdminArea()) {
                $this->messageManager->addNoticeMessage(__('Catalogsearch fulltext index has been invalidated.'));
            }
        }

        return $result;
    }

    /**
     * Check whether the attribute being saved belongs to the catalog_product entity type.
     *
     * This plugin only knows how to update the product ES mapping/index, so it must stay
     * inert for attributes of any other entity type (e.g. catalog_category) sharing the
     * same attribute resource model.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject Attribute being saved.
     *
     * @return bool
     */
    private function isProductAttribute($subject)
    {
        $entityType = $subject->getEntityType();

        return $entityType !== null && $entityType->getEntityTypeCode() === \Magento\Catalog\Model\Product::ENTITY;
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

                if ($field === 'similarity') {
                    $cleanCache = true;
                    // Updating the similarity is not possible on a field with shingles.
                    $updateMapping   = false;
                    $invalidateIndex = true;
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
            if ($this->isAdminArea()) {
                // @codingStandardsIgnoreStart
                $this->messageManager->addNoticeMessage(__('Elasticsuite mapping has been updated real-time. However, your modifications might not be visible until the Catalogsearch Fulltext index is completely rebuilt.'));
                // @codingStandardsIgnoreEnd
            }
        } catch (\Exception $exception) {
            if ($this->isAdminArea()) {
                // @codingStandardsIgnoreStart
                $this->messageManager->addErrorMessage(__('Elasticsuite mapping could not be updated real-time. Please wait for the complete reindexing of Catalogsearch Fulltext index.'));
                // @codingStandardsIgnoreEnd
            }
            $this->logger->error($exception);
        }
    }

    /**
     * Admin messages only make sense in the admin. Adding one elsewhere (e.g. in a REST API call) would start
     * a session that nobody reads.
     *
     * @return bool
     */
    private function isAdminArea()
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (LocalizedException $exception) {
            return false;
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
