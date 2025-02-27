<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule\Condition\Product;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Error\Warning;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\HasImage;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsDiscount;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\IsInStock;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\StockQty;

/**
 * Special attribute provider unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class SpecialAttributeProviderTest extends TestCase
{
    /**
     * Test listing special attributes.
     *
     * @return void
     */
    public function testGetList()
    {
        $specialAttributes = [
            'has_image'         => HasImage::class,
            'stock.is_in_stock' => IsInStock::class,
            'price.is_discount' => IsDiscount::class,
            'is_bundle'         => 'isBundleProduct',
            'is_configurable'   => 'isConfigurableProduct',
            'is_downloadable'   => 'isDownloadableProduct',
            'is_giftcard'       => 'isGiftCardProduct',
            'is_grouped'        => 'isGroupedProduct',
            'is_simple'         => 'isSimpleProduct',
            'is_created_within_last_x_days' => 'isCreatedWithinLastXdays',
            'is_updated_within_last_x_days' => 'isUpdatedWithinLastXdays',
            'stock.qty'         => StockQty::class,
        ];

        $specialAttributesProvider = new SpecialAttributesProvider($specialAttributes);
        $this->assertEquals($specialAttributes, $specialAttributesProvider->getList());

        $this->assertEquals(HasImage::class, $specialAttributesProvider->getAttribute('has_image'));
        $this->assertEquals(IsInStock::class, $specialAttributesProvider->getAttribute('stock.is_in_stock'));
        $this->assertEquals(IsDiscount::class, $specialAttributesProvider->getAttribute('price.is_discount'));
        $this->assertEquals('isBundleProduct', $specialAttributesProvider->getAttribute('is_bundle'));
        $this->assertEquals('isConfigurableProduct', $specialAttributesProvider->getAttribute('is_configurable'));
        $this->assertEquals('isDownloadableProduct', $specialAttributesProvider->getAttribute('is_downloadable'));
        $this->assertEquals('isGiftCardProduct', $specialAttributesProvider->getAttribute('is_giftcard'));
        $this->assertEquals('isGroupedProduct', $specialAttributesProvider->getAttribute('is_grouped'));
        $this->assertEquals('isSimpleProduct', $specialAttributesProvider->getAttribute('is_simple'));
        $this->assertEquals('isCreatedWithinLastXdays', $specialAttributesProvider->getAttribute('is_created_within_last_x_days'));
        $this->assertEquals('isUpdatedWithinLastXdays', $specialAttributesProvider->getAttribute('is_updated_within_last_x_days'));
        $this->assertEquals(StockQty::class, $specialAttributesProvider->getAttribute('stock.qty'));

        $this->expectWarning();
        $this->expectWarningMessage('Undefined array key "unknownAttribute"');
        $specialAttributesProvider->getAttribute('unknownAttribute');
    }
}
