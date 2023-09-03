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

namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Locale\LocaleFormatter;
use Magento\Store\Model\ScopeInterface;

/**
 * ElasticSuite product attributes helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ProductAttribute extends AbstractAttribute
{
    /**
     * @var string Config path to determine if we should use display battern on frontend.
     */
    const XML_PATH_FRONTEND_PRODUCT_DISPLAY_PATTERN_ENABLED
        = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/frontend_product_display_pattern_enabled';

    /**
     * @var string Attribute column containing pattern.
     */
    const DISPLAY_PATTERN_COLUMN = 'display_pattern';

    /**
     * @var string Attribute column containing precision.
     */
    const DISPLAY_PRECISION_COLUMN = 'display_precision';

    /**
     * @var string Default display pattern if not otherwise specified.
     */
    const DEFAULT_DISPLAY_PATTERN = '%s';

    /**
     * @var string Round value to this many decimal places if not otherwise specified.
     */
    const DEFAULT_DISPLAY_PRECISION = 2;

    /**
     * @var int The maximum possible replacements for each pattern in each subject string.
     */
    const REPLACEMENT_LIMIT = 1;

    /**
     * @var \Magento\Framework\Locale\LocaleFormatter $localeFormatter
     */
    protected $localeFormatter;

    /**
     * Constructor.
     *
     * @param Context                    $context           Helper context.
     * @param AttributeFactory           $attributeFactory  Factory used to create attributes.
     * @param AttributeCollectionFactory $collectionFactory Attribute collection factory.
     * @param LocaleFormatter            $localeFormatter   Format numbers to a locale
     */
    public function __construct(
        Context $context,
        AttributeFactory $attributeFactory,
        AttributeCollectionFactory $collectionFactory,
        LocaleFormatter $localeFormatter
    ) {
        $this->localeFormatter = $localeFormatter;
        parent::__construct($context, $attributeFactory, $collectionFactory);
    }

    /**
     * Is Display Pattern on Frontend Enabled ?
     *
     * @return bool
     */
    public function isFrontendProductDisplayPatternEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_FRONTEND_PRODUCT_DISPLAY_PATTERN_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Format Product Attribute Value with Display Pattern
     *
     * Value's stored as numeric (i.e `8` or `355`) and the attribute has a display pattern (i.e `% %s` or `%s mm`)
     *  will be formatted as `% 8` or `355 mm`
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute Product Attribute to format
     * @param int|float|string                                   $value     Product Attribute Value to format
     *
     * @return string Formatted string
     */
    public function formatProductAttributeValueDisplayPattern($attribute, $value)
    {
        // Translate attribute pattern, or default, without variable.
        // @codingStandardsIgnoreStart
        $pattern = $attribute->getData(self::DISPLAY_PATTERN_COLUMN)
            ? (string) __($attribute->getData(self::DISPLAY_PATTERN_COLUMN))
            : (string) self::DEFAULT_DISPLAY_PATTERN;
        // @codingStandardsIgnoreEnd

        // Get attribute display precision or default.
        // @codingStandardsIgnoreStart
        $precision = is_numeric($attribute->getData(self::DISPLAY_PRECISION_COLUMN) ?? '')
            ? (int) abs($attribute->getData(self::DISPLAY_PRECISION_COLUMN))
            : (int) self::DEFAULT_DISPLAY_PRECISION;
        // @codingStandardsIgnoreEnd

        // Round value to precision and format to locale string.
        $amount = (string) $this->localeFormatter->formatNumber(round((float) $value, $precision));
        // Insert number value into translated string.
        return (string) preg_replace('/' . self::DEFAULT_DISPLAY_PATTERN . '/', $amount, $pattern, self::REPLACEMENT_LIMIT);
    }
}
