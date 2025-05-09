<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusCacheConfig;

/**
 * Thesaurus Cache Config helper unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class ThesaurusCacheConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the cache storage limitation behavior.
     * @dataProvider cacheStorageLimitationDataProvider
     *
     * @param array $isSetFlagReturnsMap         Map of return results for method 'isSetFlag'.
     * @param array $getValueReturnsMap          Map of return results for method 'getValue'.
     * @param int   $storeId                     Store Id.
     * @param int   $rewritesCount               Number of rewrites/alternative queries.
     * @param bool  $expectedCacheStorageAllowed Expected cache storage allowed result.
     */
    public function testCacheStorageLimitation(
        $isSetFlagReturnsMap,
        $getValueReturnsMap,
        $storeId,
        $rewritesCount,
        $expectedCacheStorageAllowed
    ) {
        $containerConfigMock = $this->getContainerConfigurationInterfaceMock();
        $containerConfigMock->method('getStoreId')->willReturn($storeId);

        $scopeConfigMock = $this->getScopeConfigInterfaceMock();
        $scopeConfigMock->method('isSetFlag')->willReturnMap($isSetFlagReturnsMap);
        $scopeConfigMock->method('getValue')->willReturnMap($getValueReturnsMap);

        $thesaurusCacheConfig = new ThesaurusCacheConfig($scopeConfigMock);
        $this->assertEquals(
            $expectedCacheStorageAllowed,
            $thesaurusCacheConfig->isCacheStorageAllowed($containerConfigMock, $rewritesCount)
        );
    }

    /**
     * Data provider for testCacheStorageLimitation.
     *
     * @return array
     */
    public function cacheStorageLimitationDataProvider()
    {
        $isSetFlagReturnsMap = [
            [ThesaurusCacheConfig::ALWAYS_CACHE_RESULTS_XML_PATH, ScopeInterface::SCOPE_STORES, 1, true],
            [ThesaurusCacheConfig::ALWAYS_CACHE_RESULTS_XML_PATH, ScopeInterface::SCOPE_STORES, 2, false],
            [ThesaurusCacheConfig::ALWAYS_CACHE_RESULTS_XML_PATH, ScopeInterface::SCOPE_STORES, 3, false],
        ];
        $getValueReturnsMap = [
            [ThesaurusCacheConfig::MIN_REWRITES_FOR_CACHING_XML_PATH, ScopeInterface::SCOPE_STORES, 1, 10],
            [ThesaurusCacheConfig::MIN_REWRITES_FOR_CACHING_XML_PATH, ScopeInterface::SCOPE_STORES, 2, 0],
            [ThesaurusCacheConfig::MIN_REWRITES_FOR_CACHING_XML_PATH, ScopeInterface::SCOPE_STORES, 3, 10],
        ];

        return [
            /*
             * [isSetFlagReturnsMap, getValueReturnsMap, storeId, rewritesCount, expectedCacheStorageAllowed]
             */
            // StoreId 1.
            [$isSetFlagReturnsMap, $getValueReturnsMap, 1, 0, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 1, 9, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 1, 10, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 1, 11, true],
            // StoreId 2.
            [$isSetFlagReturnsMap, $getValueReturnsMap, 2, 0, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 2, 9, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 2, 10, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 2, 11, true],
            // StoreId 3.
            [$isSetFlagReturnsMap, $getValueReturnsMap, 3, 0, false],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 3, 9, false],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 3, 10, true],
            [$isSetFlagReturnsMap, $getValueReturnsMap, 3, 11, true],
        ];
    }

    /**
     * Get Container configuration mock.
     *
     * @return MockObject|ContainerConfigurationInterface
     */
    private function getContainerConfigurationInterfaceMock()
    {
        $containerConfiguration = $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $containerConfiguration;
    }

    /**
     * Get Scope config mock.
     *
     * @return MockObject|ScopeConfigInterface
     */
    private function getScopeConfigInterfaceMock()
    {
        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $scopeConfig;
    }
}
