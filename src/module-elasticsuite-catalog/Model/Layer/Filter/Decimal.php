<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

use Smile\ElasticsuiteCatalog\Model\Search\Request\Field\Mapper as RequestFieldMapper;

/**
 * Decimal filter model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Decimal extends \Magento\CatalogSearch\Model\Layer\Filter\Decimal
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
     * @var RequestFieldMapper
     */
    private $requestFieldMapper;

    /**
     * Decimal constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory                           $filterItemFactory    Filter item
     *                                                                                                        factory
     * @param \Magento\Store\Model\StoreManagerInterface                                $storeManager         The Store Manager
     * @param \Magento\Catalog\Model\Layer                                              $layer                The Layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder                      $itemDataBuilder      The data builder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory          $filterDecimalFactory Factory for
     *                                                                                                        decimal items
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface                         $priceCurrency        Price Currency
     * @param \Smile\ElasticsuiteCatalog\Model\Layer\Filter\DataProvider\DecimalFactory $dataProviderFactory  Decimal DataProvider
     *                                                                                                        Factory
     * @param \Magento\Framework\Locale\ResolverInterface                               $localeResolver       Locale Resolver
     * @param RequestFieldMapper                                                        $requestFieldMapper   Search request field
     *                                                                                                        mapper
     * @param array                                                                     $data                 Filter Data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\DecimalFactory $filterDecimalFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Smile\ElasticsuiteCatalog\Model\Layer\Filter\DataProvider\DecimalFactory $dataProviderFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        RequestFieldMapper $requestFieldMapper,
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

        $this->dataProvider       = $dataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->localeResolver     = $localeResolver;
        $this->requestFieldMapper = $requestFieldMapper;
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
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @return string
     */
    private function getFilterField()
    {
        return $this->requestFieldMapper->getMappedFieldName(
            $this->getAttributeModel()->getAttributeCode()
        );
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
            $valueFormatter = new \Magento\Framework\Filter\NormalizedToLocalized($options);
            $value = $valueFormatter->filter($value);
        }

        if ((string) $attribute->getDisplayPattern() != "") {
            $value = sprintf((string) $attribute->getDisplayPattern(), $value);
        }

        return $value;
    }

    /**
     * Create the proper query filter for price, according to current customer group Id.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param array $bounds The price bounds to apply
     *
     * @return array
     */
    private function getRangeCondition($bounds)
    {
        return $bounds;
    }
}
