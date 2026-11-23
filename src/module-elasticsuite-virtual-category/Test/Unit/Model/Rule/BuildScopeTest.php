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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Api\Rule\BuildLimitInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\BuildScope;

/**
 * Unit test for {@see BuildScope}.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class BuildScopeTest extends TestCase
{
    /**
     * Nested scopes belong to the enclosing build: the limit is started and stopped once.
     *
     * @return void
     */
    public function testNestedScopes(): void
    {
        $limit = $this->createMock(BuildLimitInterface::class);
        $limit->expects($this->once())->method('start')->with(2);
        $limit->expects($this->once())->method('stop');
        $scope = new BuildScope($limit);

        $this->assertFalse($scope->isOpen());
        $scope->open(2);
        $scope->open(3);
        $scope->close();
        $this->assertTrue($scope->isOpen());
        $scope->close();
        $this->assertFalse($scope->isOpen());
        $scope->close();
    }

    /**
     * Builds are only submitted to the limit within a scope.
     *
     * @return void
     */
    public function testBeforeBuild(): void
    {
        $limit = $this->createMock(BuildLimitInterface::class);
        $limit->expects($this->once())->method('beforeBuild')->with(12);
        $scope = new BuildScope($limit);

        $scope->beforeBuild(11);
        $scope->open(1);
        $scope->beforeBuild(12);
        $scope->close();
        $scope->beforeBuild(13);
    }
}
