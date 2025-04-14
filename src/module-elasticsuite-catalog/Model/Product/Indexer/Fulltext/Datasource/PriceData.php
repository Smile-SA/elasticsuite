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
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData as AttributeResourceModel;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData as ResourceModel;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;

/**
 * Datasource used to append prices data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class PriceData implements DatasourceInterface
{
    /** @var string */
    private const XML_PATH_COMPUTE_CHILD_PRODUCT_DISCOUNT
        = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/compute_child_product_discount';

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\PriceData
     */
    private $resourceModel;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\AttributeData
     */
    private $attributeResourceModel;

    /**
     * Scope configuration
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PriceData\PriceDataReaderInterface[]
     */
    private $priceReaderPool = [];

    /**
     * @var boolean
     */
    private $isComputeChildDiscountEnabled;

    /**
     * Constructor.
     *
     * @param ResourceModel                        $resourceModel          Resource model
     * @param AttributeResourceModel               $attributeResourceModel Attribute Resource model
     * @param PriceData\PriceDataReaderInterface[] $priceReaderPool        Price modifiers pool.
     * @param ScopeConfigInterface|null            $scopeConfig            Scope Config.
     */
    public function __construct(
        ResourceModel $resourceModel,
        AttributeResourceModel $attributeResourceModel,
        $priceReaderPool = [],
        ?ScopeConfigInterface $scopeConfig = null
    ) {
        $this->resourceModel            = $resourceModel;
        $this->priceReaderPool          = $priceReaderPool;
        $this->attributeResourceModel   = $attributeResourceModel;
        $this->scopeConfig              = $scopeConfig ?: ObjectManager::getInstance()->get(ScopeConfigInterface::class);
    }

    /**
     * Add price data to the index data.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds = array_keys($indexData);
        $priceData  = $this->resourceModel->loadPriceData($storeId, $productIds);

        if ($this->isComputeChildDiscountEnabled()) {
            $allChildrenIds = $this->attributeResourceModel->loadChildrens($productIds, $storeId);
            $childPriceData = $this->resourceModel->loadPriceData($storeId, array_keys($allChildrenIds));
        }

        foreach ($priceData as $priceDataRow) {
            $productId     = (int) $priceDataRow['entity_id'];
            $productTypeId = $indexData[$productId]['type_id'];
            $priceModifier = $this->getPriceDataReader($productTypeId);

            $originalPrice = $priceModifier->getOriginalPrice($priceDataRow);
            $price         = $priceModifier->getPrice($priceDataRow);

            $isDiscount    = $price < $originalPrice;

            if ($this->isComputeChildDiscountEnabled() &&
                in_array($productTypeId, $this->attributeResourceModel->getCompositeTypes())
            ) {
                $isDiscount = false;
                $priceModifier = $this->getPriceDataReader('default');
                foreach ($childPriceData as $childPrice) {
                    foreach ($allChildrenIds[$childPrice['entity_id']] as $childIdsData) {
                        if ($childIdsData['parent_id'] === $productId
                            && $childPrice['customer_group_id'] == $priceDataRow['customer_group_id']
                            && $priceModifier->getPrice($childPrice) < $priceModifier->getOriginalPrice($childPrice)
                        ) {
                            $isDiscount = true;
                            break 2;
                        }
                    }
                }
            }

            $indexData[$productId]['price'][] = [
                'price'             => (float) $price,
                'original_price'    => (float) $originalPrice,
                'is_discount'       => $isDiscount,
                'customer_group_id' => (int) $priceDataRow['customer_group_id'],
                'tax_class_id'      => (int) $priceDataRow['tax_class_id'],
                'final_price'       => (float) $priceDataRow['final_price'],
                'min_price'         => (float) $priceDataRow['min_price'],
                'max_price'         => (float) $priceDataRow['max_price'],
            ];

            if (!isset($indexData[$productId]['indexed_attributes'])) {
                $indexData[$productId]['indexed_attributes'] = ['price'];
            } elseif (!in_array('price', $indexData[$productId]['indexed_attributes'])) {
                // Add price only one time.
                $indexData[$productId]['indexed_attributes'][] = 'price';
            }
        }

        return $indexData;
    }

    /**
     * Retur
     * @param string $typeId Product type id.
     *
     * @return PriceData\PriceDataReaderInterface
     */
    private function getPriceDataReader($typeId)
    {
        $priceModifier = $this->priceReaderPool['default'];

        if (isset($this->priceReaderPool[$typeId])) {
            $priceModifier = $this->priceReaderPool[$typeId];
        }

        return $priceModifier;
    }

    /**
     * Is computing child product discount enabled.
     *
     * @return bool
     */
    private function isComputeChildDiscountEnabled(): bool
    {
        if (!isset($this->isIndexingChildProductSkuEnabled)) {
            $this->isComputeChildDiscountEnabled = (bool) $this->scopeConfig->getValue(
                self::XML_PATH_COMPUTE_CHILD_PRODUCT_DISCOUNT,
                ScopeInterface::SCOPE_STORE
            );
        }

        return $this->isComputeChildDiscountEnabled;
    }
}
