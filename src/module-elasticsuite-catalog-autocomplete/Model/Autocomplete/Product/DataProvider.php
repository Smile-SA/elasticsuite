<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalogAutocomplete
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalogAutocomplete\Model\Autocomplete\Product;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticSuiteCatalogAutocomplete\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as ProductCollectionFactory;
use \Smile\ElasticSuiteCatalogAutocomplete\Helper\Configuration as ConfigurationHelper;

/**
 * Catalog product autocomplete data provider.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogAutocomplete
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete result item factory
     *
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * Query factory
     *
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var TermDataProvider
     */
    protected $termDataProvider;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $imageHelper;

    /**
     * @var ConfigurationHelper
     */
    protected $configurationHelper;

    /**
     * Constructor.
     *
     * @param ItemFactory                     $itemFactory              Suggest item factory.
     * @param QueryFactory                    $queryFactory             Search query factory.
     * @param TermDataProvider                $termDataProvider         Search terms suggester.
     * @param ProductCollectionFactory        $productCollectionFactory Product collection factory.
     * @param \Magento\Catalog\Helper\Product $productHelper            Catalog Image helper.
     * @param ConfigurationHelper             $configurationHelper      Autocomplete configuration helper.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        TermDataProvider $termDataProvider,
        ProductCollectionFactory $productCollectionFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        ConfigurationHelper $configurationHelper
    ) {
        $this->itemFactory              = $itemFactory;
        $this->queryFactory             = $queryFactory;
        $this->termDataProvider         = $termDataProvider;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper              = $productHelper;
        $this->configurationHelper      = $configurationHelper;
    }
    /**
     * {@inheritDoc}
     */
    public function getItems()
    {
        $result = [];
        $productCollection = $this->getProductCollection();
        if ($productCollection) {
            \Magento\Catalog\Model\Product::ATTRIBUTE_SET_ID;
            foreach ($productCollection as $product) {
                $result[] = $this->itemFactory->create(
                    [
                        'title'       => $product->getName(),
                        'image'       => $this->imageHelper->getSmallImageUrl($product),
                        'url'         => $product->getProductUrl(),
                        'price'       => $product->getFinalPrice(),
                        'final_price' => $product->getPrice(), // The getPrice method returns always 0.
                        'type'        => 'product',
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * List of search terms suggested by the search terms data daprovider.
     *
     * @return array
     */
    private function getSuggestedTerms()
    {
        $terms = array_map(
            function (\Magento\Search\Model\Autocomplete\Item $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        return $terms;
    }

    /**
     * Suggested products collection.
     * Returns null if no suggested search terms.
     *
     * @return \Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection|null
     */
    private function getProductCollection()
    {
        $productCollection = null;
        $suggestedTerms = $this->getSuggestedTerms();
        $terms          = [$this->queryFactory->get()->getQueryText()];

        if (!empty($suggestedTerms)) {
            $terms = array_merge($terms, $suggestedTerms);
        }

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addSearchFilter($terms);
        $productCollection->setPageSize($this->getResultsPageSize());
        $productCollection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('small_image')
            ->addPriceData();

        return $productCollection;
    }

    /**
     * Retrieve number of products to display in autocomplete results
     *
     * @return int
     */
    private function getResultsPageSize()
    {
        return $this->configurationHelper->getProductsMaxSize();
    }
}
