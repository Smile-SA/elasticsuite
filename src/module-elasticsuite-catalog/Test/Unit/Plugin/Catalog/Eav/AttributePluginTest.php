<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Alexandru-Manuel Carabus <46504593+Hawksama@users.noreply.github.com>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Test\Unit\Plugin\Catalog\Eav;

use Magento\Catalog\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCatalog\Helper\ProductAttribute as AttributeHelper;
use Smile\ElasticsuiteCatalog\Plugin\Catalog\Eav\AttributePlugin;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Index\Indices\Config as IndicesConfig;

/**
 * Catalog EAV attribute plugin unit test.
 *
 * The plugin is plugged on the attribute resource model, which is called both by the attribute model (admin)
 * and directly by the product attribute repository (REST/SOAP API).
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Alexandru-Manuel Carabus <46504593+Hawksama@users.noreply.github.com>
 */
#[AllowMockObjectsWithoutExpectations]
class AttributePluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IndicesConfig&MockObject
     */
    private $indicesConfig;

    /**
     * @var IndexerInterface&MockObject
     */
    private $fulltextIndexer;

    /**
     * @var IndexOperationInterface&MockObject
     */
    private $indexOperation;

    /**
     * @var ManagerInterface&MockObject
     */
    private $messageManager;

    /**
     * @var State&MockObject
     */
    private $appState;

    /**
     * @var AttributeResource&MockObject
     */
    private $attributeResource;

    /**
     * @var StoreInterface&MockObject
     */
    private $store;

    /**
     * @var AttributePlugin
     */
    private $plugin;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->indicesConfig     = $this->createMock(IndicesConfig::class);
        $this->fulltextIndexer   = $this->createMock(IndexerInterface::class);
        $this->indexOperation    = $this->createMock(IndexOperationInterface::class);
        $this->messageManager    = $this->createMock(ManagerInterface::class);
        $this->appState          = $this->createMock(State::class);
        $this->attributeResource = $this->createMock(AttributeResource::class);
        $this->store             = $this->createMock(StoreInterface::class);

        $indexerRegistry = $this->createMock(IndexerRegistry::class);
        $indexerRegistry->method('get')->willReturn($this->fulltextIndexer);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$this->store]);

        $attributeHelper = $this->createMock(AttributeHelper::class);
        $attributeHelper->method('getFilterField')->willReturn('option_text_material');
        $attributeHelper->method('getMappingFieldOptions')->willReturn(['is_filterable' => true]);

        $this->plugin = new AttributePlugin(
            $this->indicesConfig,
            $indexerRegistry,
            $storeManager,
            $this->indexOperation,
            $attributeHelper,
            $this->messageManager,
            $this->createMock(LoggerInterface::class),
            $this->appState
        );
    }

    /**
     * A new product attribute saved through the resource model (e.g. POST /V1/products/attributes) must reset the
     * indices config, add the attribute fields to the mapping and invalidate the fulltext index.
     *
     * @return void
     */
    public function testNewProductAttributeSavedThroughApiUpdatesMapping()
    {
        $attribute = $this->getAttribute('catalog_product', true);
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_WEBAPI_REST);

        $this->indicesConfig->expects($this->once())->method('reset');
        $this->indexOperation->expects($this->once())
            ->method('updateMapping')
            ->with('catalog_product', $this->store, ['material', 'option_text_material']);
        $this->fulltextIndexer->expects($this->once())->method('invalidate');
        $this->messageManager->expects($this->never())->method('addNoticeMessage');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->save($attribute);
    }

    /**
     * Saving the same attribute from the admin keeps showing the notices.
     *
     * @return void
     */
    public function testNewProductAttributeSavedInAdminShowsNotices()
    {
        $attribute = $this->getAttribute('catalog_product', true);
        $this->appState->method('getAreaCode')->willReturn(Area::AREA_ADMINHTML);

        $this->indicesConfig->expects($this->once())->method('reset');
        $this->fulltextIndexer->expects($this->once())->method('invalidate');
        $this->messageManager->expects($this->exactly(2))->method('addNoticeMessage');

        $this->save($attribute);
    }

    /**
     * Without an area code (e.g. a CLI script), the attribute is handled but no message is added.
     *
     * @return void
     */
    public function testNewProductAttributeSavedWithoutAreaAddsNoMessage()
    {
        $attribute = $this->getAttribute('catalog_product', true);
        $this->appState->method('getAreaCode')
            ->willThrowException(new \Magento\Framework\Exception\LocalizedException(__('Area code is not set')));

        $this->indicesConfig->expects($this->once())->method('reset');
        $this->messageManager->expects($this->never())->method('addNoticeMessage');

        $this->save($attribute);
    }

    /**
     * Attributes of other entity types share the same resource model and must be left alone.
     *
     * @return void
     */
    public function testCategoryAttributeIsIgnored()
    {
        $attribute = $this->getAttribute('catalog_category', true);

        $this->indicesConfig->expects($this->never())->method('reset');
        $this->indexOperation->expects($this->never())->method('updateMapping');
        $this->fulltextIndexer->expects($this->never())->method('invalidate');

        $this->save($attribute);
    }

    /**
     * An existing attribute saved without any search related change triggers nothing.
     *
     * @return void
     */
    public function testUnchangedExistingAttributeTriggersNothing()
    {
        $attribute = $this->getAttribute('catalog_product', false);
        $attribute->method('dataHasChangedFor')->willReturn(false);

        $this->indicesConfig->expects($this->never())->method('reset');
        $this->indexOperation->expects($this->never())->method('updateMapping');
        $this->fulltextIndexer->expects($this->never())->method('invalidate');

        $this->save($attribute);
    }

    /**
     * Run the plugin around a resource model save, the way the interceptor does.
     *
     * @param Attribute $attribute Attribute being saved.
     *
     * @return void
     */
    private function save(Attribute $attribute)
    {
        $this->plugin->beforeSave($this->attributeResource, $attribute);
        $result = $this->plugin->afterSave($this->attributeResource, $this->attributeResource, $attribute);

        $this->assertSame($this->attributeResource, $result);
    }

    /**
     * Build an attribute mock.
     *
     * @param string $entityTypeCode Entity type code.
     * @param bool   $isNew          Is the attribute being created.
     *
     * @return Attribute&MockObject
     */
    private function getAttribute($entityTypeCode, $isNew)
    {
        $entityType = $this->createMock(EntityType::class);
        $entityType->method('getEntityTypeCode')->willReturn($entityTypeCode);

        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getEntityType')->willReturn($entityType);
        $attribute->method('isObjectNew')->willReturn($isNew);
        $attribute->method('getAttributeCode')->willReturn('material');
        $attribute->method('getOrigData')->willReturn([]);

        return $attribute;
    }
}
