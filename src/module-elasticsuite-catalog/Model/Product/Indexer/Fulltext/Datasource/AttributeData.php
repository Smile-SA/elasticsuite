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

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCatalog\Helper\AbstractAttribute as AttributeHelper;
use Smile\ElasticsuiteCatalog\Model\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Eav\Indexer\Fulltext\Datasource\AbstractAttributeData as ResourceModel;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Index\Mapping\FieldFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TranslateInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\AreaList;

/**
 * Datasource used to index product attributes.
 * This class is also used to generate attribute mapping since it implements DynamicFieldProviderInterface.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeData extends AbstractAttributeData implements DatasourceInterface, DynamicFieldProviderInterface
{
    /** @var string */
    private const XML_PATH_INDEX_CHILD_PRODUCT_SKU = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/index_child_product_sku';

    /**
     * Scope configuration
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $forbiddenChildrenAttributes = [];

    /**
     * @var boolean
     */
    private $isIndexingChildProductSkuEnabled;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var TranslateInterface
     */
    private $translator;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var AreaList
     */
    private $areaList;

    /**
     * Constructor
     *
     * @param ResourceModel             $resourceModel               Resource model.
     * @param FieldFactory              $fieldFactory                Mapping field factory.
     * @param AttributeHelper           $attributeHelper             Attribute helper.
     * @param array                     $indexedBackendModels        List of indexed backend models added to the default list.
     * @param array                     $forbiddenChildrenAttributes List of the forbidden children attributes.
     * @param ScopeConfigInterface|null $scopeConfig                 Scope Config.
     */
    public function __construct(
        ResourceModel $resourceModel,
        FieldFactory $fieldFactory,
        AttributeHelper $attributeHelper,
        array $indexedBackendModels = [],
        array $forbiddenChildrenAttributes = [],
        ?ScopeConfigInterface $scopeConfig = null,
        ?ResolverInterface $localeResolver = null,
        ?TranslateInterface $translator = null,
        ?State $appState = null,
        ?AreaList $areaList = null
    ) {
        parent::__construct($resourceModel, $fieldFactory, $attributeHelper, $indexedBackendModels);

        $this->scopeConfig = $scopeConfig;
        $this->forbiddenChildrenAttributes = array_values($forbiddenChildrenAttributes);
        $this->localeResolver = $localeResolver ?? ObjectManager::getInstance()->get(ResolverInterface::class);
        $this->translator = $translator ?? ObjectManager::getInstance()->get(TranslateInterface::class);
        $this->appState = $appState ?? ObjectManager::getInstance()->get(State::class);
        $this->areaList = $areaList ?? ObjectManager::getInstance()->get(AreaList::class);
    }

    /**
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        // load store translation for static attribute options
        $this->localeResolver->emulate($storeId);
        $this->translator->setLocale($this->localeResolver->getLocale())->loadData(null, true);
        // Translate area part may not be loaded :
        $area = $this->areaList->getArea($this->appState->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);

        $productIds   = array_keys($indexData);
        $indexData    = $this->addAttributeData($storeId, $productIds, $indexData);

        $relationsByChildId = $this->resourceModel->loadChildrens($productIds, $storeId);

        if (!empty($relationsByChildId)) {
            $allChildrenIds      = array_keys($relationsByChildId);
            $childrenIndexData   = $this->addAttributeData($storeId, $allChildrenIds);

            foreach ($childrenIndexData as $childrenId => $childrenData) {
                $enabled = isset($childrenData['status']) && current($childrenData['status']) == 1;
                if ($enabled === false) {
                    unset($childrenIndexData[$childrenId]);
                }
            }

            foreach ($relationsByChildId as $childId => $relations) {
                foreach ($relations as $relation) {
                    $parentId = (int) $relation['parent_id'];
                    if (isset($indexData[$parentId]) && isset($childrenIndexData[$childId])) {
                        $indexData[$parentId]['children_ids'][] = $childId;
                        $this->addRelationData($indexData[$parentId], $childrenIndexData[$childId], $relation);
                        $this->addChildData($indexData[$parentId], $childrenIndexData[$childId]);
                        $this->addChildSku($indexData[$parentId], $relation);
                    }
                }
            }
        }

        // reinitialize translation
        $this->localeResolver->revert();
        $this->translator->setLocale($this->localeResolver->getLocale())->loadData(null, true);

        return $this->filterCompositeProducts($indexData);
    }

    /**
     * Append attribute data to the index.
     *
     * @param int   $storeId    Indexed store id.
     * @param array $productIds Indexed product ids.
     * @param array $indexData  Original indexed data.
     *
     * @return array
     */
    private function addAttributeData($storeId, $productIds, $indexData = [])
    {
        foreach ($this->attributeIdsByTable as $backendTable => $attributeIds) {
            $attributesData = $this->loadAttributesRawData($storeId, $productIds, $backendTable, $attributeIds);
            foreach ($attributesData as $row) {
                $productId   = (int) $row['entity_id'];
                $indexValues = $this->attributeHelper->prepareIndexValue($row['attribute_id'], $storeId, $row['value']);
                if (!isset($indexData[$productId])) {
                    $indexData[$productId] = [];
                }

                $indexData[$productId] += $indexValues;

                $this->addIndexedAttribute($indexData[$productId], $row['attribute_code']);
            }
        }

        return $indexData;
    }

    /**
     * Append data of child products to the parent.
     *
     * @param array $parentData      Parent product data.
     * @param array $childAttributes Child product attributes data.
     *
     * @return void
     */
    private function addChildData(&$parentData, $childAttributes)
    {
        $authorizedChildAttributes = $parentData['children_attributes'];
        $addedChildAttributesData  = array_filter(
            $childAttributes,
            function ($attributeCode) use ($authorizedChildAttributes) {
                return in_array($attributeCode, $authorizedChildAttributes);
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($addedChildAttributesData as $attributeCode => $value) {
            if (!isset($parentData[$attributeCode])) {
                $parentData[$attributeCode] = [];
            }

            $parentData[$attributeCode] = array_values(array_unique(array_merge($parentData[$attributeCode], $value)));
        }
    }

    /**
     * Append relation information to the index for composite products.
     *
     * @param array $parentData      Parent product data.
     * @param array $childAttributes Child product attributes data.
     * @param array $relation        Relation data between the child and the parent.
     *
     * @return void
     */
    private function addRelationData(&$parentData, $childAttributes, $relation)
    {
        $childAttributeCodes  = array_keys($childAttributes);

        if (!isset($parentData['children_attributes'])) {
            $parentData['children_attributes'] = ['indexed_attributes'];
        }

        $childrenAttributes = array_merge(
            $parentData['children_attributes'],
            array_diff($childAttributeCodes, $this->forbiddenChildrenAttributes)
        );

        if (isset($relation['configurable_attributes']) && !empty($relation['configurable_attributes'])) {
            $attributesCodes = array_map(
                function (int $attributeId) {
                    if (isset($this->attributesById[$attributeId])) {
                        return $this->attributesById[$attributeId]->getAttributeCode();
                    }
                },
                $relation['configurable_attributes']
            );

            $parentData['configurable_attributes'] = array_values(
                array_unique(
                    array_merge($attributesCodes, $parentData['configurable_attributes'] ?? [])
                )
            );
        }

        $parentData['children_attributes'] = array_values(array_unique($childrenAttributes));
    }

    /**
     * Filter out composite product when no enabled children are attached.
     *
     * @param array $indexData Indexed data.
     *
     * @return array
     */
    private function filterCompositeProducts($indexData)
    {
        $compositeProductTypes = $this->resourceModel->getCompositeTypes();

        foreach ($indexData as $productId => $productData) {
            $isComposite = in_array($productData['type_id'], $compositeProductTypes);
            $hasChildren = isset($productData['children_ids']) && !empty($productData['children_ids']);
            if ($isComposite && !$hasChildren) {
                unset($indexData[$productId]);
            }
        }

        return $indexData;
    }

    /**
     * Append SKU of children product to the parent product index data.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array $parentData Parent product data.
     * @param array $relation   Relation data between the child and the parent.
     */
    private function addChildSku(&$parentData, $relation)
    {
        if (isset($parentData['sku']) && !is_array($parentData['sku'])) {
            $parentData['sku'] = [$parentData['sku']];
        }

        if (!$this->isIndexChildProductSkuEnabled()) {
            $parentData['sku'][] = $relation['sku'];
            $parentData['sku'] = array_unique($parentData['sku']);
        } else {
            $parentData['children_skus'][] = $relation['sku'];
            $parentData['children_skus'] = array_unique($parentData['children_skus']);
        }
    }

    /**
     * Append an indexed attributes to indexed data of a given product.
     *
     * @param array  $productIndexData Product Index data
     * @param string $attributeCode    The attribute code
     */
    private function addIndexedAttribute(&$productIndexData, $attributeCode)
    {
        if (!isset($productIndexData['indexed_attributes'])) {
            $productIndexData['indexed_attributes'] = [];
        }

        // Data can be missing for this attribute (Eg : due to null value being escaped,
        // or this attribute is already included in the array).
        if (isset($productIndexData[$attributeCode])
            && !in_array($attributeCode, $productIndexData['indexed_attributes'])
        ) {
            $productIndexData['indexed_attributes'][] = $attributeCode;
        }
    }

    /**
     * Is indexing child product SKU in dedicated subfield enabled?
     *
     * @return bool
     */
    private function isIndexChildProductSkuEnabled(): bool
    {
        if (!isset($this->isIndexingChildProductSkuEnabled)) {
            $this->isIndexingChildProductSkuEnabled = (bool) $this->getScopeConfig()->getValue(
                self::XML_PATH_INDEX_CHILD_PRODUCT_SKU,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->isIndexingChildProductSkuEnabled;
    }

    /**
     * Get Scope Config object. It can be null to allow BC.
     *
     * @return \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private function getScopeConfig() : ScopeConfigInterface
    {
        if (null === $this->scopeConfig) {
            $this->scopeConfig = ObjectManager::getInstance()->get(ScopeConfigInterface::class);
        }

        return $this->scopeConfig;
    }
}
