<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Decimal filter model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Decimal extends \Magento\CatalogSearch\Model\Layer\Filter\Decimal implements FilterInterface
{
    use DecimalFilterTrait;

    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
     */
    private $dataProvider;

    /**
     * Locale interface
     *
     * @var \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    private $localeResolver;

    /**
     * Decimal constructor.
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                  $filterItemFactory    Filter item
     *                                                                                               factory
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManager         The Store Manager
     * @param \Magento\Catalog\Model\Layer                                     $layer                The Layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder             $itemDataBuilder      The data builder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory $filterDecimalFactory Factory for
     *                                                                                               decimal items
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface                $priceCurrency        Price Currency
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory    $dataProviderFactory  Price DataProvider
     *                                                                                               Factory
     * @param \Magento\Framework\Locale\ResolverInterface                      $localeResolver       Locale Resolver
     * @param array                                                            $data                 Filter Data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory $filterDecimalFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $filterDecimalFactory,
            $priceCurrency,
            $data
        );
        $this->localeResolver = $localeResolver;
        $this->dataProvider   = $dataProviderFactory->create(['layer' => $this->getLayer()]);
    }

    /**
     * {@inheritDoc}
     */
    public function addFacetToCollection($config = [])
    {
        $facetField = $this->getFilterField();
        $facetType = BucketInterface::TYPE_HISTOGRAM;
        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFacet($facetField, $facetType, ['minDocCount' => 1]);

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _renderRangeLabel($fromValue, $toValue)
    {
        $label = $this->formatValue($fromValue);

        if ($toValue === '') {
            $label = __('%1 and above', $label);
        } elseif ($fromValue != $toValue) {
            $label = __('%1 - %2', $label, $this->formatValue($toValue));
        }

        return $label;
    }

    /**
     * Retrieve ES filter field.
     *
     * @return string
     */
    private function getFilterField()
    {
        $field = $this->getAttributeModel()->getAttributeCode();

        return $field;
    }

    /**
     * Format value according to attribute display options
     *
     * @param mixed $value The value to format
     *
     * @return string
     */
    private function formatValue($value)
    {
        $attribute = $this->getAttributeModel();

        if ((int) $attribute->getDisplayPrecision() > 0) {
            $locale = $this->localeResolver->getLocale();
            $options = ['locale' => $locale, 'precision' => (int) $attribute->getDisplayPrecision()];
            $valueFormatter = new \Zend_Filter_NormalizedToLocalized($options);
            $value = $valueFormatter->filter($value);
        }

        if ((string) $attribute->getDisplayPattern() != "") {
            $value = sprintf((string) $attribute->getDisplayPattern(), $value);
        }

        return $value;
    }
}
