<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Helper\Mapping as MappingHelper;

/**
 * List of attributes used in query building.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeList
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface
     */
    private $indexManager;

    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $typeName;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $attributeCollection = null;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\MappingInterface
     */
    private $mapping;

    /**
     * @var \Smile\ElasticsuiteCore\Helper\Mapping
     */
    private $mappingHelper;

    /**
     * @var @array
     */
    private $fieldNameMapping = [
        'price'        => 'price.price',
        'category_ids' => 'category.category_id',
    ];

    /**
     * Constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory Product attribute collection factory.
     * @param StoreManagerInterface      $storeManager               Store manager.
     * @param IndexOperationInterface    $indexManager               Search engine index manager.
     * @param MappingHelper              $mappingHelper              Mapping helper.
     * @param string                     $indexName                  Search engine index name.
     * @param string                     $typeName                   Search engine type name.
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        StoreManagerInterface $storeManager,
        IndexOperationInterface $indexManager,
        MappingHelper $mappingHelper,
        $indexName = 'catalog_product',
        $typeName = 'product'
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->storeManager               = $storeManager;
        $this->indexManager               = $indexManager;
        $this->indexName                  = $indexName;
        $this->typeName                   = $typeName;
        $this->mappingHelper              = $mappingHelper;
    }

    /**
     * Retrieve attribute collection prefiltered with only attribute usable in rules.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    {
        if ($this->attributeCollection === null) {
            $this->attributeCollection = $this->attributeCollectionFactory->create();
            $attributeNameMapping      = array_flip($this->fieldNameMapping);

            $arrayNameCb = function (FieldInterface $field) use ($attributeNameMapping) {
                $attributeName = $field->getName();

                if (isset($attributeNameMapping[$attributeName])) {
                    $attributeName = $attributeNameMapping[$attributeName];
                }

                return $attributeName;
            };

            $fieldNames = array_map($arrayNameCb, $this->getMapping()->getFields());

            $this->attributeCollection->addFieldToFilter('attribute_code', $fieldNames)
                 ->addFieldToFilter('backend_type', ['neq' => 'datetime']);
        }

        return $this->attributeCollection;
    }

    /**
     * Retrieve the mapping field for the rule attribute.
     *
     * @param string $attributeName Attribute code.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getField($attributeName)
    {
        if (isset($this->fieldNameMapping[$attributeName])) {
            $attributeName = $this->fieldNameMapping[$attributeName];
        }

        return $this->getMapping()->getField($attributeName);
    }

    /**
     * Retrieve the search engine mapping.
     *
     * @return MappingInterface
     */
    private function getMapping()
    {
        if ($this->mapping === null) {
            $defaultStore = $this->getDefaultStoreView();
            $index        = $this->indexManager->getIndexByName($this->indexName, $defaultStore);

            $this->mapping = $index->getMapping();
        }

        return $this->mapping;
    }

    /**
     * Retrieve default Store View
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    private function getDefaultStoreView()
    {
        $store = $this->storeManager->getDefaultStoreView();
        if (null === $store) {
            // Occurs when current user does not have access to default website (due to AdminGWS ACLS on Magento EE).
            $store = current($this->storeManager->getWebsites())->getDefaultStore();
        }

        return $store;
    }
}
