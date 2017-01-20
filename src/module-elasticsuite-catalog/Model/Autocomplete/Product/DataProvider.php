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
namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalog\Helper\Autocomplete as ConfigurationHelper;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection as ProductCollection;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Magento\Catalog\Model\Product\Visibility;

/**
 * Catalog product autocomplete data provider.
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
    const AUTOCOMPLETE_TYPE = "product";

    /**
     * Autocomplete result item factory
     *
     * @var ItemFactory
     */
    private $itemFactory;

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
     * @var ConfigurationHelper
     */
    private $configurationHelper;

    /**
     * @var string Autocomplete result type
     */
    private $type;

    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * Additional product attributes required.
     *
     * @var array
     */
    private $additionalAttributes;

    /**
     * Constructor.
     *
     * @param ItemFactory         $itemFactory          Suggest item factory.
     * @param QueryFactory        $queryFactory         Search query factory.
     * @param TermDataProvider    $termDataProvider     Search terms suggester.
     * @param ProductCollection   $productCollection    Product collection.
     * @param ConfigurationHelper $configurationHelper  Autocomplete configuration helper.
     * @param string              $type                 Autocomplete provider type.
     * @param array               $additionalAttributes Additional product attributes required.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        TermDataProvider $termDataProvider,
        ProductCollection $productCollection,
        ConfigurationHelper $configurationHelper,
        $type = self::AUTOCOMPLETE_TYPE,
        array $additionalAttributes = []
    ) {
        $this->itemFactory              = $itemFactory;
        $this->queryFactory             = $queryFactory;
        $this->termDataProvider         = $termDataProvider;
        $this->productCollection        = $productCollection;
        $this->configurationHelper      = $configurationHelper;
        $this->type                     = $type;
        $this->additionalAttributes     = $additionalAttributes;

        $this->prepareProductCollection();
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

        if ($this->configurationHelper->isEnabled($this->getType())) {
            foreach ($this->productCollection as $product) {
                $result[] = $this->itemFactory->create([
                    'product'               => $product,
                    'type'                  => $this->getType(),
                    'additional_attributes' => $this->additionalAttributes,
                ]);
            }
        }

        return $result;
    }

    /**
     * Init suggested products collection.
     *
     * @return \Smile\ElasticsuiteCatalog\Model\Autocomplete\Product\DataProvider
     */
    private function prepareProductCollection()
    {
        $terms = $this->getQueryText();
        $this->productCollection->addSearchFilter($terms);
        $this->productCollection->setPageSize($this->getResultsPageSize());
        $this->productCollection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('thumbnail')
            ->setVisibility([Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH])
            ->addPriceData();

        if ($this->additionalAttributes) {
            $this->productCollection->addAttributeToSelect($this->additionalAttributes);
        }

        if (!$this->configurationHelper->isShowOutOfStock()) {
            $this->productCollection->addIsInStockFilter();
        }

        return $this;
    }

    /**
     * List of search terms suggested by the search terms data provider.
     *
     * @return array
     */
    private function getQueryText()
    {
        $terms = array_map(
            function (\Magento\Search\Model\Autocomplete\Item $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        if (empty($terms)) {
            $terms = [$this->queryFactory->get()->getQueryText()];
        }

        return $terms;
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
