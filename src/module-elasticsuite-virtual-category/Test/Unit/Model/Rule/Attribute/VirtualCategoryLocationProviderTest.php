<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule\Attribute;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\Attribute\VirtualCategoryLocationProvider;

/**
 * Unit test for {@see VirtualCategoryLocationProvider}.
 *
 * This test verifies that the provider correctly determines whether
 * an attribute is used in Virtual Category rules stored in EAV.
 *
 * Covered scenarios:
 * - Attribute is present in virtual category rules
 * - Attribute is not present
 * - virtual_rule attribute does not exist
 * - Empty attribute input (short-circuit)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class VirtualCategoryLocationProviderTest extends TestCase
{
    /**
     * Test that isPresent() returns TRUE when attribute exists in virtual category rules.
     *
     * Scenario:
     * - virtual_rule attribute exists
     * - DB query returns count > 0
     */
    public function testIsPresentReturnsTrueWhenAttributeExists(): void
    {
        $connectionMock = $this->createMock(AdapterInterface::class);
        $resourceMock   = $this->createMock(ResourceConnection::class);
        $selectMock     = $this->createMock(Select::class);
        $attributeMock  = $this->createMock(AbstractAttribute::class);
        $attributeRepo  = $this->createMock(AttributeRepositoryInterface::class);

        // Mock attribute metadata.
        $attributeMock->method('getBackendTable')
            ->willReturn('catalog_category_text');

        $attributeMock->method('getAttributeId')
            ->willReturn(123);

        $attributeRepo->method('get')
            ->with('catalog_category', 'virtual_rule')
            ->willReturn($attributeMock);

        // Mock resource.
        $resourceMock->method('getConnection')
            ->willReturn($connectionMock);

        $resourceMock->method('getTableName')
            ->with('catalog_category_text')
            ->willReturn('catalog_category_text');

        // Mock select chain.
        $connectionMock->method('select')
            ->willReturn($selectMock);

        $selectMock->method('from')->willReturnSelf();
        $selectMock->method('where')->willReturnSelf();
        $selectMock->method('limit')->willReturnSelf();

        // Ensure query execution.
        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn(1);

        $provider = new VirtualCategoryLocationProvider(
            $resourceMock,
            $attributeRepo
        );

        $this->assertTrue($provider->isPresent('activity'));
    }

    /**
     * Test that isPresent() returns FALSE when attribute is not used.
     *
     * Scenario:
     * - virtual_rule attribute exists
     * - DB query returns 0
     */
    public function testIsPresentReturnsFalseWhenAttributeDoesNotExist(): void
    {
        $connectionMock = $this->createMock(AdapterInterface::class);
        $resourceMock   = $this->createMock(ResourceConnection::class);
        $selectMock     = $this->createMock(Select::class);
        $attributeMock  = $this->createMock(AbstractAttribute::class);
        $attributeRepo  = $this->createMock(AttributeRepositoryInterface::class);

        $attributeMock->method('getBackendTable')
            ->willReturn('catalog_category_text');

        $attributeMock->method('getAttributeId')
            ->willReturn(123);

        $attributeRepo->method('get')
            ->willReturn($attributeMock);

        $resourceMock->method('getConnection')
            ->willReturn($connectionMock);

        $resourceMock->method('getTableName')
            ->willReturn('catalog_category_text');

        $connectionMock->method('select')
            ->willReturn($selectMock);

        $selectMock->method('from')->willReturnSelf();
        $selectMock->method('where')->willReturnSelf();
        $selectMock->method('limit')->willReturnSelf();

        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with($selectMock)
            ->willReturn(0);

        $provider = new VirtualCategoryLocationProvider(
            $resourceMock,
            $attributeRepo
        );

        $this->assertFalse($provider->isPresent('non_existing_attribute'));
    }

    /**
     * Test that isPresent() returns FALSE when virtual_rule attribute does not exist.
     *
     * Scenario:
     * - AttributeRepository throws NoSuchEntityException
     * - Method should safely return FALSE
     */
    public function testIsPresentReturnsFalseWhenAttributeDoesNotExistInEav(): void
    {
        $resourceMock  = $this->createMock(ResourceConnection::class);
        $attributeRepo = $this->createMock(AttributeRepositoryInterface::class);

        $attributeRepo->method('get')
            ->willThrowException(new NoSuchEntityException(__('Attribute not found')));

        $provider = new VirtualCategoryLocationProvider(
            $resourceMock,
            $attributeRepo
        );

        $this->assertFalse($provider->isPresent('activity'));
    }

    /**
     * Test that isPresent() returns FALSE for empty attribute.
     *
     * Ensures:
     * - No DB query is executed
     * - No attribute lookup is performed
     */
    public function testIsPresentReturnsFalseForEmptyAttribute(): void
    {
        $resourceMock  = $this->createMock(ResourceConnection::class);
        $attributeRepo = $this->createMock(AttributeRepositoryInterface::class);

        // Ensure repository is never called.
        $attributeRepo->expects($this->never())
            ->method('get');

        $provider = new VirtualCategoryLocationProvider(
            $resourceMock,
            $attributeRepo
        );

        $this->assertFalse($provider->isPresent(''));
    }
}
