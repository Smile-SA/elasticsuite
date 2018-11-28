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
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product price filter implementation.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price implements FilterInterface
{
    use DecimalFilterTrait;

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
     */
    private $dataProvider;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory               $filterItemFactory   Item filter facotory.
     * @param \Magento\Store\Model\StoreManagerInterface                    $storeManager        Store manager.
     * @param \Magento\Catalog\Model\Layer                                  $layer               Search layer.
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder          $itemDataBuilder     Item data builder.
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price       $resource            Price resource.
     * @param \Magento\Customer\Model\Session                               $customerSession     Customer session.
     * @param \Magento\Framework\Search\Dynamic\Algorithm                   $priceAlgorithm      Price algorithm.
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface             $priceCurrency       Price currency.
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory  $algorithmFactory    Algorithm factory.
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory Data provider.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory     $queryFactory        Query Factory.
     * @param array                                                         $data                Custom data.
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );

        $this->dataProvider    = $dataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->customerSession = $customerSession;
        $this->priceCurrency   = $priceCurrency;
        $this->queryFactory    = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection($config = [])
    {
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $facetConfig = [
            'name'         => $this->getFilterField(),
            'type'         => BucketInterface::TYPE_HISTOGRAM,
            'nestedFilter' => ['price.customer_group_id' => $customerGroupId], 'minDocCount' => 1,
        ];

        $calculation = $this->dataProvider->getRangeCalculationValue();
        if ($calculation === \Magento\Catalog\Model\Layer\Filter\DataProvider\Price::RANGE_CALCULATION_MANUAL) {
            if ((int) $this->dataProvider->getRangeStepValue() > 0) {
                $facetConfig['interval'] = (int) $this->dataProvider->getRangeStepValue();
            }
        }

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetConfig);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _renderRangeLabel($fromPrice, $toPrice)
    {
        $formattedPrice = $this->priceCurrency->format((float) $fromPrice * $this->getCurrencyRate());

        if ($toPrice === '') {
            $formattedPrice = __('%1 and above', $formattedPrice);
        } elseif ($fromPrice != $toPrice || !$this->dataProvider->getOnePriceIntervalValue()) {
            $toPrice        = (float) $toPrice * $this->getCurrencyRate();
            $formattedPrice = __('%1 - %2', $formattedPrice, $this->priceCurrency->format($toPrice));
        }

        return $formattedPrice;
    }

    /**
     * Retrieve ES filter field.
     *
     * @return string
     */
    private function getFilterField()
    {
        return 'price.price';
    }

    /**
     * Create the proper query filter for price, according to current customer group Id.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param array $bounds The price bounds to apply
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getRangeCondition($bounds)
    {
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $priceQuery = $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path'  => 'price',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'price.customer_group_id', 'value' => $customerGroupId]
                            ),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_RANGE,
                                ['field' => 'price.price', 'bounds' => $bounds]
                            ),
                        ],
                    ]
                ),
            ]
        );

        return $priceQuery;
    }
}
