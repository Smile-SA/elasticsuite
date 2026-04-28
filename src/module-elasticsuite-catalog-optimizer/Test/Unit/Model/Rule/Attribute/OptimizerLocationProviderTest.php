<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Model\Rule\Attribute;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogOptimizer\Model\Rule\Attribute\OptimizerLocationProvider;

/**
 * Unit test for {@see OptimizerLocationProvider}.
 *
 * This test suite validates the behavior of the location provider responsible
 * for detecting whether a given attribute is used inside Elasticsuite
 * Catalog Optimizer rules.
 *
 * The provider relies on a database query using a LIKE condition against
 * the `rule_condition` column. Since this is a unit test, the database layer
 * is fully mocked.
 *
 * Covered scenarios:
 * - Attribute is present in at least one rule (positive case)
 * - Attribute is not present in any rule (negative case)
 * - Empty attribute input handling
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class OptimizerLocationProviderTest extends TestCase
{
    /**
     * Test that `isPresent()` returns TRUE when the attribute exists in rules.
     *
     * Scenario:
     * - DB query returns a count greater than zero
     * - Method should return TRUE
     *
     * @return void
     */
    public function testIsPresentReturnsTrueWhenAttributeExists(): void
    {
        $connectionMock = $this->createMock(AdapterInterface::class);
        $resourceMock   = $this->createMock(ResourceConnection::class);
        $selectMock     = $this->createMock(Select::class);

        // Table name.
        $resourceMock->method('getTableName')
            ->willReturn('smile_elasticsuite_optimizer');

        // Connection.
        $resourceMock->method('getConnection')
            ->willReturn($connectionMock);

        // Mock select builder chain.
        $connectionMock->method('select')
            ->willReturn($selectMock);

        $selectMock->method('from')
            ->willReturnSelf();

        $selectMock->method('where')
            ->willReturnSelf();

        $selectMock->method('limit')
            ->willReturnSelf();

        // Ensure query is executed.
        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn(1);

        $provider = new OptimizerLocationProvider($resourceMock);

        $this->assertTrue($provider->isPresent('activity'));
    }

    /**
     * Test that `isPresent()` returns FALSE when the attribute does not exist.
     *
     * Scenario:
     * - DB query returns zero
     * - Method should return FALSE
     *
     * @return void
     */
    public function testIsPresentReturnsFalseWhenAttributeDoesNotExist(): void
    {
        $connectionMock = $this->createMock(AdapterInterface::class);
        $resourceMock   = $this->createMock(ResourceConnection::class);
        $selectMock     = $this->createMock(Select::class);

        // Mock table name.
        $resourceMock->method('getTableName')
            ->willReturn('smile_elasticsuite_optimizer');

        // Mock DB connection.
        $resourceMock->method('getConnection')
            ->willReturn($connectionMock);

        // Mock select builder.
        $connectionMock->method('select')
            ->willReturn($selectMock);

        $selectMock->method('from')
            ->willReturnSelf();

        $selectMock->method('where')
            ->willReturnSelf();

        $selectMock->method('limit')
            ->willReturnSelf();

        // Simulate no matching rows.
        $connectionMock->method('fetchOne')
            ->with($selectMock)
            ->willReturn(0);

        $provider = new OptimizerLocationProvider($resourceMock);

        $this->assertFalse($provider->isPresent('non_existing_attribute'));
    }

    /**
     * Test that `isPresent()` returns FALSE for empty attribute input.
     *
     * Scenario:
     * - Empty string is passed
     * - Method should short-circuit and return FALSE without querying DB
     *
     * @return void
     */
    public function testIsPresentReturnsFalseForEmptyAttribute(): void
    {
        $connectionMock = $this->createMock(AdapterInterface::class);
        $resourceMock   = $this->createMock(ResourceConnection::class);

        // Ensure DB is never called.
        $connectionMock->expects($this->never())
            ->method('fetchOne');

        $resourceMock->method('getConnection')
            ->willReturn($connectionMock);

        $provider = new OptimizerLocationProvider($resourceMock);

        $this->assertFalse($provider->isPresent(''));
    }
}
