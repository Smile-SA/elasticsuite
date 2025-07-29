<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Index\Indices\Config as IndicesConfig;

/**
 * Index settings test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pigau@smile.fr>
 */
class IndexSettingsTest extends TestCase
{
    /**
     * Test index name parsing.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @dataProvider parseIndexNameDataProvider
     *
     * @param string      $indexName     Index name.
     * @param string      $alias         Index prefix.
     * @param string      $suffixPattern Index suffix pattern.
     * @param array|false $expected      Expected result.
     *
     * @return void
     */
    public function testParseIndexName(string $indexName, string $alias, string $suffixPattern, $expected)
    {
        $scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $scopeConfigMock->method('getValue')
            ->willReturnCallback(function ($path) use ($alias, $suffixPattern) {
                $values = [
                    'smile_elasticsuite_core_base_settings/indices_settings/alias' => $alias,
                    'smile_elasticsuite_core_base_settings/indices_settings/indices_pattern' => $suffixPattern,
                ];

                return $values[$path] ?? null;
            });

        $contextMock = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $contextMock->method('getScopeConfig')->willReturn($scopeConfigMock);
        $storeManager = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $indicesConfig = $this->getMockBuilder(IndicesConfig::class)->disableOriginalConstructor()->getMock();
        $indicesConfig
            ->method('get')
            ->will(
                $this->returnValue(
                    [
                        'catalog' => 'indexConfiguration',
                        'catalog_product' => 'indexConfiguration',
                        'tracking_log_session' => 'indexConfiguration',
                    ]
                )
            );

        $indexSettings = new IndexSettingsHelper($contextMock, $storeManager, $indicesConfig);
        $result = $indexSettings->parseIndexName($indexName);

        if ($expected === false) {
            $this->assertFalse($result);
        } else {
            $this->assertCount(count($expected), $result);
            $this->assertSame($expected['prefix'], $result['prefix']);
            $this->assertSame($expected['store_code'], $result['store_code']);
            $this->assertSame($expected['index_identifier'], $result['index_identifier']);
            if (is_object($result['datetime'])) {
                $format = str_replace(['{{', '}}'], '', $suffixPattern);
                $this->assertSame($expected['datetime']->format($format), $result['datetime']->format($format));
            } else {
                $this->assertFalse($result['datetime']);
            }
        }
    }

    /**
     * Parse index name test data provider.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @return iterable
     */
    public static function parseIndexNameDataProvider(): iterable
    {
        yield [
            'magento2_default_catalog_product_20250707_093823',
            'magento2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento2',
                'store_code' => 'default',
                'index_identifier' => 'catalog_product',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
        yield [
            'magento_2_default_catalog_product_20250707_093823',
            'magento2',
            '{{Ymd}}_{{His}}',
            false,
        ];
        yield [
            'magento_2_default_catalog_product_20250707_093823',
            'magento_2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento_2',
                'store_code' => 'default',
                'index_identifier' => 'catalog_product',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
        yield [
            'magento_2_default_catalog_20250707_093823',
            'magento_2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento_2',
                'store_code' => 'default',
                'index_identifier' => 'catalog',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
        yield [
            'magento2_store_fr_catalog_product_20250707_093823',
            'magento2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento2',
                'store_code' => 'store_fr',
                'index_identifier' => 'catalog_product',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
        yield [
            'magento2_default_catalog_product_20250701',
            'magento2',
            '{{Ymd}}',
            [
                'prefix' => 'magento2',
                'store_code' => 'default',
                'index_identifier' => 'catalog_product',
                'datetime' => \DateTime::createFromFormat('Ymd', '20250701'),
            ],
        ];
        yield [
            'magento2_default_tracking_log_session_202507',
            'magento2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento2',
                'store_code' => 'default',
                'index_identifier' => 'tracking_log_session',
                'datetime' => false,
            ],
        ];
        yield [
            'magento2_shop_catalog_product_catalog_product_20250707_093823',
            'magento2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento2',
                'store_code'  => 'shop_catalog_product',
                'index_identifier'   => 'catalog_product',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
        yield [
            'magento2_shop_catalog_product_catalog_20250707_093823',
            'magento2',
            '{{Ymd}}_{{His}}',
            [
                'prefix' => 'magento2',
                'store_code'  => 'shop_catalog_product',
                'index_identifier'   => 'catalog',
                'datetime' => \DateTime::createFromFormat('Ymd_His', '20250707_093823'),
            ],
        ];
    }
}
