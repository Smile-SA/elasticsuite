<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Attribute;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\Item as AutocompleteItem;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Helper\Autocomplete as AutocompleteHelper;

/**
 * Catalog product attributes autocomplete data provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete type
     */
    const AUTOCOMPLETE_TYPE = "product_attribute";

    /**
     * Autocomplete result item factory
     *
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var string Autocomplete result type
     */
    private $type;

    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var AttributeCollection
     */
    private $attributeCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AutocompleteHelper
     */
    private $autocompleteHelper;

    /**
     * Constructor.
     *
     * @param ItemFactory                $itemFactory                Autocomplete item factory.
     * @param ProductCollection          $productCollection          Autocomplete product collection.
     * @param AttributeCollectionFactory $attributeCollectionFactory Product attribute collection factory.
     * @param AutocompleteHelper         $autocompleteHelper         Autocomplete configuration helper.
     * @param StoreManagerInterface      $storeManager               Store manager.
     * @param string                     $type                       Autocomplete type code.
     */
    public function __construct(
        ItemFactory $itemFactory,
        ProductCollection $productCollection,
        AttributeCollectionFactory $attributeCollectionFactory,
        AutocompleteHelper $autocompleteHelper,
        StoreManagerInterface $storeManager,
        $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->itemFactory         = $itemFactory;
        $this->type                = $type;
        $this->storeManager        = $storeManager;
        $this->productCollection   = $productCollection;
        $this->attributeCollection = $attributeCollectionFactory->create();
        $this->autocompleteHelper  = $autocompleteHelper;

        $this->loadAttributeCollection();
        $this->prepareProductCollection();
    }

    /**
     * Returns autocomplete type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getItems()
    {
        $items = [];

        if ($this->autocompleteHelper->isEnabled($this->getType())) {
            foreach ($this->attributeCollection as $attribute) {
                $filterField = $this->getFilterField($attribute);
                $facetData   = $this->productCollection->getFacetedData($filterField);

                foreach ($facetData as $currentFilter) {
                    if ($currentFilter['value'] != '__other_docs') {
                        $currentFilter['attribute_code']  = $attribute->getAttributeCode();
                        $currentFilter['attribute_label'] = $attribute->getStoreLabel();
                        $currentFilter['type']            = $this->getType();
                        $items[] = $this->itemFactory->create($currentFilter);
                    }
                }
            }

            uasort($items, [$this, 'resultSorterCallback']);

            $items = array_slice($items, 0, $this->getResultsPageSize());
        }

        return $items;
    }

    /**
     * Append facets used to select suggested attributes.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Attribute\DataProvider
     */
    private function prepareProductCollection()
    {
        foreach ($this->attributeCollection as $attribute) {
            $facetSize   = $this->getResultsPageSize();
            $filterField = $this->getFilterField($attribute);
            $this->productCollection->addFacet($filterField, BucketInterface::TYPE_TERM, ['size' => $facetSize]);
        }

        return $this;
    }

    /**
     * Load the attributes displayed in the suggest.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Attribute\DataProvider
     */
    private function loadAttributeCollection()
    {
        $storeId = $this->storeManager->getStore()->getId();

        $this->attributeCollection->addStoreLabel($storeId)
            ->addFieldToFilter('is_displayed_in_autocomplete', true)
            ->load();

        return $this;
    }

    /**
     * Get filter field for an attribute.
     *
     * @param Magento\Catalog\Model\ResourceModel\Product\Attribute $attribute Product attribute.
     *
     * @return string
     */
    private function getFilterField($attribute)
    {
        return $this->autocompleteHelper->getAttributeAutocompleteField($attribute);
    }

    /**
     * Retrieve number of products to display in autocomplete results
     *
     * @return int
     */
    private function getResultsPageSize()
    {
        return $this->autocompleteHelper->getMaxSize($this->getType());
    }

    /**
     * Sort autocomplete items by result count.
     *
     * @param AutocompleteItem $item1 First sorted item
     * @param AutocompleteItem $item2 Second sorted item
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @return integer
     */
    private function resultSorterCallback(AutocompleteItem $item1, AutocompleteItem $item2)
    {
        return $item2->getCount() - $item1->getCount();
    }
}
