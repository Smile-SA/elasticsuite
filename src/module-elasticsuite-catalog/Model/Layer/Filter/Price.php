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
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory;
use Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Search\Dynamic\Algorithm;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Price as FilterPrice;
use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
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
class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    use DecimalFilterTrait;

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
     */
    private $dataProvider;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param ItemFactory            $filterItemFactory   Item filter factory.
     * @param StoreManagerInterface  $storeManager        Store manager.
     * @param Layer                  $layer               Search layer.
     * @param DataBuilder            $itemDataBuilder     Item data builder.
     * @param FilterPrice            $resource            Price resource.
     * @param Session                $customerSession     Customer session.
     * @param Algorithm              $priceAlgorithm      Price algorithm.
     * @param PriceCurrencyInterface $priceCurrency       Price currency.
     * @param AlgorithmFactory       $algorithmFactory    Algorithm factory.
     * @param PriceFactory           $dataProviderFactory Data provider.
     * @param QueryFactory           $queryFactory        Query Factory.
     * @param RequestFieldMapper     $requestFieldMapper  Search request field mapper.
     * @param array                  $data                Custom data.
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        FilterPrice $resource,
        Session $customerSession,
        Algorithm $priceAlgorithm,
        PriceCurrencyInterface $priceCurrency,
        AlgorithmFactory $algorithmFactory,
        PriceFactory $dataProviderFactory,
        QueryFactory $queryFactory,
        RequestFieldMapper $requestFieldMapper,
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

        $this->dataProvider       = $dataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->customerSession    = $customerSession;
        $this->priceCurrency      = $priceCurrency;
        $this->queryFactory       = $queryFactory;
        $this->requestFieldMapper = $requestFieldMapper;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * {@inheritDoc}
     */
    protected function _renderRangeLabel($fromPrice, $toPrice, $isLast = false)
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
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @return string
     */
    private function getFilterField()
    {
        return $this->requestFieldMapper->getMappedFieldName('price');
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
