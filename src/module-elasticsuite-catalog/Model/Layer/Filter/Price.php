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
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

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
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection($config = [])
    {
        $facetField      = $this->getFilterField();
        $facetType       = BucketInterface::TYPE_HISTOGRAM;
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $facetConfig = ['nestedFilter' => ['price.customer_group_id' => $customerGroupId], 'minDocCount' => 1];

        $calculation = $this->dataProvider->getRangeCalculationValue();
        if ($calculation === \Magento\Catalog\Model\Layer\Filter\DataProvider\Price::RANGE_CALCULATION_MANUAL) {
            if ((int) $this->dataProvider->getRangeStepValue() > 0) {
                $facetConfig['interval'] = (int) $this->dataProvider->getRangeStepValue();
            }
        }

        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, $facetConfig);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _renderRangeLabel($fromPrice, $toPrice)
    {
        $formattedPrice = $this->priceCurrency->format($fromPrice);

        if ($toPrice === '') {
            $formattedPrice = __('%1 and above', $formattedPrice);
        } elseif ($fromPrice != $toPrice || !$this->dataProvider->getOnePriceIntervalValue()) {
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
}
