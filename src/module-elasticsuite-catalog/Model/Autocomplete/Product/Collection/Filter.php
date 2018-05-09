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

use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Magento\Search\Model\Autocomplete\Item as TermItem;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;

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
     * Query factory
     *
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var TermDataProvider
     */
    private $termDataProvider;

    /**
     * Constructor.
     *
     * @param QueryFactory     $queryFactory     Search term query factory.
     * @param TermDataProvider $termDataProvider Popular search terms provider.
     */
    public function __construct(QueryFactory $queryFactory, TermDataProvider $termDataProvider)
    {
        $this->queryFactory     = $queryFactory;
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
        $terms = $this->getQueryText();

        $collection->setSearchQuery($terms);

        return $collection;
    }

    /**
     * List of search terms suggested by the search terms data provider.
     *
     * @return array
     */
    private function getQueryText()
    {
        $terms = array_map(
            function (TermItem $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        if (empty($terms)) {
            $terms = [$this->queryFactory->get()->getQueryText()];
        }

        return $terms;
    }
}
