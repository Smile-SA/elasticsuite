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
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\AttributeConfig;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Collection\Provider;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
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
     * @var AttributeConfig
     */
    private $attributeConfig;

    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var AutocompleteHelper
     */
    private $autocompleteHelper;

    /**
     * Constructor.
     *
     * @param ItemFactory        $itemFactory               Autocomplete item factory.
     * @param AttributeConfig    $attributeConfig           Autocomplete attribute config.
     * @param Provider           $productCollectionProvider Autocomplete product collection provider.
     * @param AutocompleteHelper $autocompleteHelper        Autocomplete configuration helper.
     * @param string             $type                      Autocomplete type code.
     */
    public function __construct(
        ItemFactory $itemFactory,
        AttributeConfig $attributeConfig,
        Provider $productCollectionProvider,
        AutocompleteHelper $autocompleteHelper,
        $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->itemFactory         = $itemFactory;
        $this->type                = $type;
        $this->attributeConfig     = $attributeConfig;
        $this->productCollection   = $productCollectionProvider->getProductCollection();
        $this->autocompleteHelper  = $autocompleteHelper;
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
            foreach ($this->attributeConfig->getAutocompleteAttributeCollection() as $attribute) {
                $filterField = $this->attributeConfig->getFilterField($attribute);
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
