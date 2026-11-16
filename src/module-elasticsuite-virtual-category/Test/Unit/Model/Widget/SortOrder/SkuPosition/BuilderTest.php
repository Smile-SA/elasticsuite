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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Widget\SortOrder\SkuPosition;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition\Builder;

/**
 * Unit test for {@see Builder}.
 *
 * Covered scenarios:
 * - SKUs are handled as values by the database adapter, both in the filter and in the sort order
 * - Empty SKUs are ignored, but not a "0" SKU
 * - No query is run when there is no SKU left
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class BuilderTest extends TestCase
{
    /**
     * Test that the SKUs are passed as values to the database adapter, in the filter and in the sort order.
     *
     * @return void
     */
    public function testSkusAreQuotedByTheAdapter(): void
    {
        $expectedSkus = ['SKU-1', "A'B", '0'];
        $orderExpression = "FIELD(e.sku, 'SKU-1', 'A\\'B', '0')";

        $select = $this->createMock(Select::class);
        $select->method('from')->with(['e' => 'catalog_product_entity'], ['entity_id'])->willReturnSelf();
        $select->expects($this->once())->method('where')->with('e.sku IN (?)', $expectedSkus)->willReturnSelf();
        $select->method('reset')->with(Select::ORDER)->willReturnSelf();
        $select->expects($this->once())->method('order')
            ->with($this->callback(function ($expression) use ($orderExpression) {
                return (string) $expression === $orderExpression;
            }))
            ->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->expects($this->once())->method('quoteInto')
            ->with('FIELD(e.sku, ?)', $expectedSkus)
            ->willReturn($orderExpression);
        $connection->expects($this->once())->method('fetchCol')->with($select)->willReturn(['12', '7', '3']);

        $sortOrder = $this->getBuilder($connection)->buildSortOrder(['SKU-1', '', "A'B", '0']);

        $this->assertSame([12 => 1, 7 => 2, 3 => 3], $sortOrder['_script']['params']['scores']);
    }

    /**
     * Test that no query is run when there is no SKU left once the empty ones are ignored.
     *
     * @return void
     */
    public function testNoQueryWithoutSkus(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->never())->method('select');
        $connection->expects($this->never())->method('fetchCol');

        $sortOrder = $this->getBuilder($connection)->buildSortOrder(['', '']);

        $this->assertSame([], $sortOrder['_script']['params']['scores']);
    }

    /**
     * Return the tested builder, using the given database connection.
     *
     * @param AdapterInterface $connection Database connection.
     *
     * @return Builder
     */
    private function getBuilder(AdapterInterface $connection): Builder
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);

        $metadata = $this->createMock(EntityMetadataInterface::class);
        $metadata->method('getIdentifierField')->willReturn('entity_id');
        $metadata->method('getEntityTable')->willReturn('catalog_product_entity');

        $metadataPool = $this->createMock(MetadataPool::class);
        $metadataPool->method('getMetadata')->with(ProductInterface::class)->willReturn($metadata);

        return new Builder($resource, $metadataPool);
    }
}
