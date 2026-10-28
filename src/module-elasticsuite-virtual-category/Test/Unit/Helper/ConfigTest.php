<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Helper;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;

/**
 * Unit test for the store root category and the expansion boundaries settings of {@see Config}.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
#[AllowMockObjectsWithoutExpectations]
class ConfigTest extends TestCase
{
    /**
     * Test the store root category setting, read at the default scope.
     *
     * @return void
     */
    public function testIsStoreRootUsedAsStandardCategory(): void
    {
        foreach ([true, false] as $value) {
            $scopeConfig = $this->createMock(ScopeConfigInterface::class);
            $scopeConfig->method('isSetFlag')
                ->with(Config::XML_PATH_STORE_ROOT_AS_STANDARD_CATEGORY)
                ->willReturn($value);

            $this->assertSame($value, (new Config($scopeConfig))->isStoreRootUsedAsStandardCategory());
        }
    }

    /**
     * Test the parsing of the expansion boundaries setting.
     *
     * @dataProvider expansionBoundariesDataProvider
     *
     * @param string|null $value       Setting value.
     * @param int[]       $expectedIds Expected category ids.
     *
     * @return void
     */
    #[DataProvider('expansionBoundariesDataProvider')]
    public function testGetVirtualRulesExpansionBoundaries(?string $value, array $expectedIds): void
    {
        $this->assertSame($expectedIds, $this->getConfig($value)->getVirtualRulesExpansionBoundaries());
    }

    /**
     * Data provider for {@see testGetVirtualRulesExpansionBoundaries()}.
     *
     * @return array
     */
    public static function expansionBoundariesDataProvider(): array
    {
        return [
            'not set'               => [null, []],
            'empty'                 => ['', []],
            'single id'             => ['8089', [8089]],
            'spaces and duplicates' => [' 8168, 8089 ,8168', [8089, 8168]],
            'invalid values'        => ['abc,0,-3,12.5,,8089', [8089]],
        ];
    }

    /**
     * Test the cache key part of the expansion boundaries.
     *
     * @return void
     */
    public function testGetVirtualRulesExpansionBoundariesCacheKey(): void
    {
        $this->assertNull($this->getConfig('')->getVirtualRulesExpansionBoundariesCacheKey());
        $this->assertNull($this->getConfig('abc')->getVirtualRulesExpansionBoundariesCacheKey());

        $cacheKey = $this->getConfig('8089,8168')->getVirtualRulesExpansionBoundariesCacheKey();
        $this->assertStringStartsWith('b', $cacheKey);
        $this->assertSame($cacheKey, $this->getConfig(' 8168,8089,8168')->getVirtualRulesExpansionBoundariesCacheKey());
        $this->assertNotSame($cacheKey, $this->getConfig('8089')->getVirtualRulesExpansionBoundariesCacheKey());
    }

    /**
     * Config helper reading an expansion boundaries setting.
     *
     * @param string|null $value Setting value.
     *
     * @return Config
     */
    private function getConfig(?string $value): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with(Config::XML_PATH_RULES_EXPANSION_BOUNDARIES)
            ->willReturn($value);

        return new Config($scopeConfig);
    }
}
