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
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Collection;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
use Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\AttributeConfig;

/**
 * Catalog autocomplete product collection attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeSelector implements PreProcessorInterface
{
    /**
     * @var string[]
     */
    private $attributes;

    /**
     * Constructor.
     *
     * @param AttributeConfig $selectedAttributes Attribute selected.
     */
    public function __construct(AttributeConfig $selectedAttributes)
    {
        $this->attributes = $selectedAttributes->getSelectedAttributeCodes();
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
        $collection->addAttributeToSelect($this->attributes);
        $collection->addPriceData();

        return $collection;
    }
}
