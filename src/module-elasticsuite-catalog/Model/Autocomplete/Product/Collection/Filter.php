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
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\Collection;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
use Smile\ElasticsuiteCore\Model\Autocomplete\SuggestedTermsProvider;

/**
 * Catalog autocomplete product collection filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Filter implements PreProcessorInterface
{
    /**
     * @var TermDataProvider
     */
    private $termDataProvider;

    /**
     * Constructor.
     *
     * @param SuggestedTermsProvider $termDataProvider Suggested search terms provider.
     */
    public function __construct(SuggestedTermsProvider $termDataProvider)
    {
        $this->termDataProvider = $termDataProvider;
    }

    /**
     * Append filters to the product list :
     *    - Search query filter
     *
     * @param ProductCollection $collection Product collection.
     *
     * @return ProductCollection
     */
    public function prepareCollection(ProductCollection $collection)
    {
        $collection->setSearchQuery($this->termDataProvider->getSuggestedTerms());

        return $collection;
    }
}
