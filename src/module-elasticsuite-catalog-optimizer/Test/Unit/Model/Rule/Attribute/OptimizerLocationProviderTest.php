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

use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogOptimizer\Api\Rule\Attribute\OptimizerCollectionFilterInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Rule\Attribute\OptimizerLocationProvider;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;

/**
 * Unit test for {@see OptimizerLocationProvider}.
 *
 * This test validates the behavior of the location provider responsible
 * for determining whether a given product attribute is used in Elasticsuite Catalog Optimizer rules.
 *
 * Key responsibilities of the provider:
 * - Create optimizer collection
 * - Apply injected collection filters (agnostic to module origin)
 * - Apply attribute LIKE filter on `rule_condition`
 * - Limit query to 1 result for performance
 * - Return TRUE/FALSE depending on collection size
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class OptimizerLocationProviderTest extends TestCase
{
    /**
     * Test that isPresent() returns TRUE when attribute exists.
     *
     * Scenario:
     * - Attribute is found in at least one optimizer rule
     * - Collection size > 0
     *
     * Verifies:
     * - Collection is created
     * - All filters are applied
     * - LIKE filter is added correctly
     * - limit(1) is applied to select
     * - TRUE is returned
     */
    public function testIsPresentReturnsTrueWhenAttributeExists(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $filterMock = $this->createMock(OptimizerCollectionFilterInterface::class);

        // Mock Select object.
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['limit'])
            ->getMock();

        // Expect limit(1) call for optimization.
        $selectMock->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();

        // Collection factory should create collection.
        $collectionFactoryMock->method('create')
            ->willReturn($collectionMock);

        // Ensure filter is applied.
        $filterMock->expects($this->once())
            ->method('apply')
            ->with($collectionMock);

        // Ensure attribute LIKE filter is applied correctly.
        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with(
                'rule_condition',
                $this->callback(function ($condition) {
                    return isset($condition['like'])
                        && str_contains($condition['like'], '"attribute":"activity"');
                })
            )
            ->willReturnSelf();

        // Attach mocked Select.
        $collectionMock->method('getSelect')
            ->willReturn($selectMock);

        // Simulate that matching records exist.
        $collectionMock->method('getSize')
            ->willReturn(1);

        $provider = new OptimizerLocationProvider(
            $collectionFactoryMock,
            [$filterMock]
        );

        $this->assertTrue($provider->isPresent('activity'));
    }

    /**
     * Test that isPresent() returns FALSE when attribute does not exist.
     *
     * Scenario:
     * - No optimizer rule contains the attribute
     * - Collection size = 0
     *
     * Verifies:
     * - Same flow as positive test
     * - FALSE is returned when no results found
     */
    public function testIsPresentReturnsFalseWhenAttributeDoesNotExist(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $filterMock = $this->createMock(OptimizerCollectionFilterInterface::class);

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['limit'])
            ->getMock();

        $selectMock->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();

        $collectionFactoryMock->method('create')
            ->willReturn($collectionMock);

        $filterMock->expects($this->once())
            ->method('apply')
            ->with($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $collectionMock->method('getSelect')
            ->willReturn($selectMock);

        // Simulate no results.
        $collectionMock->method('getSize')
            ->willReturn(0);

        $provider = new OptimizerLocationProvider(
            $collectionFactoryMock,
            [$filterMock]
        );

        $this->assertFalse($provider->isPresent('non_existing_attribute'));
    }

    /**
     * Test that isPresent() returns FALSE for empty attribute.
     *
     * Scenario:
     * - Empty string is provided
     *
     * Verifies:
     * - No collection is created
     * - No filters are applied
     * - Method returns FALSE immediately
 */
    public function testIsPresentReturnsFalseForEmptyAttribute(): void
    {
        $collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $filterMock = $this->createMock(OptimizerCollectionFilterInterface::class);

        // Ensure no interaction happens.
        $collectionFactoryMock->expects($this->never())
            ->method('create');

        $filterMock->expects($this->never())
            ->method('apply');

        $provider = new OptimizerLocationProvider(
            $collectionFactoryMock,
            [$filterMock]
        );

        $this->assertFalse($provider->isPresent(''));
    }
}
