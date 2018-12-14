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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Collection;

use Smile\ElasticsuiteCatalog\Helper\Autocomplete as AutocompleteHelper;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\AttributeConfig;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Attribute\DataProvider;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Catalog autocomplete product collection displayed attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeAggregation implements PreProcessorInterface
{
    /**
     * @var AttributeConfig
     */
    private $attributeConfig;

    /**
     * @var AutocompleteHelper
     */
    private $autocompleteHelper;

    /**
     * Constructor
     *
     * @param AttributeConfig    $attributeConfig    Autocomplete attribute config.
     * @param AutocompleteHelper $autocompleteHelper Autocomplete helper.
     */
    public function __construct(AttributeConfig $attributeConfig, AutocompleteHelper $autocompleteHelper)
    {
        $this->attributeConfig    = $attributeConfig;
        $this->autocompleteHelper = $autocompleteHelper;
    }

    /**
     * Add standard attributes and price data to the collection.
     *
     * @param ProductCollection $collection Product collection.
     *
     * @return ProductCollection
     */
    public function prepareCollection(ProductCollection $collection)
    {
        foreach ($this->attributeConfig->getAutocompleteAttributeCollection() as $attribute) {
            $facetSize   = $this->autocompleteHelper->getMaxSize(DataProvider::AUTOCOMPLETE_TYPE);
            $filterField = $this->attributeConfig->getFilterField($attribute);
            $collection->addFacet(['name' => $filterField, 'type' => BucketInterface::TYPE_TERM, 'size' => $facetSize]);
        }

        return $collection;
    }
}
