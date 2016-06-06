<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Model\Autocomplete\Product;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticSuiteCatalog\Helper\Autocomplete as ConfigurationHelper;
use Smile\ElasticSuiteCatalog\Model\ResourceModel\Product\Fulltext\CollectionFactory as ProductCollectionFactory;
use Smile\ElasticSuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;

/**
 * Catalog product autocomplete data provider.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete type
     */
    const AUTOCOMPLETE_TYPE = "product";

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
     * @var ConfigurationHelper
     */
    protected $configurationHelper;

    /**
     * @var string Autocomplete result type
     */
    private $type;

    /**
     * Constructor.
     *
     * @param ItemFactory              $itemFactory              Suggest item factory.
     * @param QueryFactory             $queryFactory             Search query factory.
     * @param TermDataProvider         $termDataProvider         Search terms suggester.
     * @param ProductCollectionFactory $productCollectionFactory Product collection factory.
     * @param ConfigurationHelper      $configurationHelper      Autocomplete configuration helper.
     * @param string                   $type                     Autocomplete provider type.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        TermDataProvider $termDataProvider,
        ProductCollectionFactory $productCollectionFactory,
        ConfigurationHelper $configurationHelper,
        $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->itemFactory              = $itemFactory;
        $this->queryFactory             = $queryFactory;
        $this->termDataProvider         = $termDataProvider;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configurationHelper      = $configurationHelper;
        $this->type                      = $type;
    }

    /**
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
        $result = [];
        $productCollection = $this->getProductCollection();
        if ($productCollection) {
            foreach ($productCollection as $product) {
                $result[] = $this->itemFactory->create(['product' => $product, 'type' => $this->getType()]);
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

        if (!$this->configurationHelper->isShowOutOfStock()) {
            $productCollection->addIsInStockFilter();
        }

        return $productCollection;
    }

    /**
     * Retrieve number of products to display in autocomplete results
     *
     * @return int
     */
    private function getResultsPageSize()
    {
        return $this->configurationHelper->getMaxSize($this->getType());
    }
}
