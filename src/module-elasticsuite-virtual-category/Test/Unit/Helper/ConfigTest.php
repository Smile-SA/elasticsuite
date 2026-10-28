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
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;

/**
 * Unit test for the store root category setting of {@see Config}.
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
}
