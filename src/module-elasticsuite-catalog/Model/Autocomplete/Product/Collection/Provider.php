<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Collection;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;

/**
 * Catalog autocomplete product collection provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Provider
{
    /**
     * @var ProductCollection
     */
    private $collection;

    /**
     * @var PreProcessorInterface[]
     */
    private $collectionProcessors;


    /**
     * Constructor.
     *
     * @param ProductCollection $collection           Product collection.
     * @param array             $collectionProcessors Product collection preprocessors.
     */
    public function __construct(ProductCollection $collection, $collectionProcessors = [])
    {
        $this->collectionProcessors = $collectionProcessors;
        $this->collection           = $this->prepareProductCollection($collection);
    }

    /**
     * Product collection used in autocomplete.
     *
     * @return ProductCollection
     */
    public function getProductCollection()
    {
        return $this->collection;
    }

    /**
     * Init suggested products collection.
     *
     * @param ProductCollection $collection Product collection
     *
     * @return ProductCollection
     */
    private function prepareProductCollection(ProductCollection $collection)
    {
        foreach ($this->collectionProcessors as $processor) {
            $collection = $processor->prepareCollection($collection);
        }

        return $collection;
    }
}
