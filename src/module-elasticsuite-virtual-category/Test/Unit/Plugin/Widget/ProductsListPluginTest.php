<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Ilan Parmentier <dev@amadeco.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Plugin\Widget;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Helper\Conditions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider;
use Smile\ElasticsuiteVirtualCategory\Model\Widget\SortOrder\SkuPosition\Builder as SkuPositionSortOrderBuilder;
use Smile\ElasticsuiteVirtualCategory\Plugin\Widget\ProductsListPlugin;

/**
 * Unit test for {@see ProductsListPlugin::afterGetBaseCollection()}.
 *
 * The search page of a widget collection must cover the documents its SQL limit reads from.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Ilan Parmentier <dev@amadeco.fr>
 */
class ProductsListPluginTest extends TestCase
{
    /**
     * @var ProductsListPlugin
     */
    private $plugin;

    /**
     * @var ProductsList
     */
    private $subject;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->plugin = new ProductsListPlugin(
            $this->createStub(StoreManagerInterface::class),
            $this->createStub(CategoryRepositoryInterface::class),
            $this->createStub(Conditions::class),
            $this->createStub(SkuPositionSortOrderBuilder::class),
            $this->createStub(Provider::class),
            $this->createStub(QueryFactory::class)
        );
        $this->subject = $this->createStub(ProductsList::class);
    }

    /**
     * Test the search page size is computed from the SQL limit.
     *
     * @param int      $count            SQL limit count.
     * @param int|null $offset           SQL limit offset.
     * @param int      $expectedPageSize Expected search page size.
     *
     * @return void
     */
    #[DataProvider('limitedSelectDataProvider')]
    public function testSearchPageSizeFollowsSqlLimit(int $count, ?int $offset, int $expectedPageSize): void
    {
        $collection = $this->createSearchCollection($count, $offset);
        $collection->expects($this->once())->method('setPageSize')->with($expectedPageSize)->willReturnSelf();
        $collection->expects($this->once())->method('setCurPage')->with(1)->willReturnSelf();

        $this->assertSame($collection, $this->plugin->afterGetBaseCollection($this->subject, $collection));
    }

    /**
     * Widget without pager, pager on its first page, pager on a short last page.
     *
     * @return array
     */
    public static function limitedSelectDataProvider(): array
    {
        return [
            'products count only'    => [4, null, 4],
            'pager, first page'      => [5, 0, 5],
            'pager, short last page' => [2, 8, 10],
        ];
    }

    /**
     * Test a select without a positive limit keeps the legacy behavior.
     *
     * @param int|null $count  SQL limit count.
     * @param int|null $offset SQL limit offset.
     *
     * @return void
     */
    #[DataProvider('unlimitedSelectDataProvider')]
    public function testUnlimitedSelectIsLeftAlone(?int $count, ?int $offset): void
    {
        $collection = $this->createSearchCollection($count, $offset);
        $collection->expects($this->never())->method('setPageSize');
        $collection->expects($this->never())->method('setCurPage');

        $this->assertSame($collection, $this->plugin->afterGetBaseCollection($this->subject, $collection));
    }

    /**
     * No limit at all, and a pager page beyond the widget total (zero count).
     *
     * @return array
     */
    public static function unlimitedSelectDataProvider(): array
    {
        return [
            'no limit'                    => [null, null],
            'zero count beyond the total' => [0, 12],
        ];
    }

    /**
     * Test a database collection, which honours its SQL limit, is not touched.
     *
     * @return void
     */
    public function testDatabaseCollectionIsLeftAlone(): void
    {
        $collection = $this->createMock(ProductCollection::class);
        $collection->expects($this->never())->method('setPageSize');

        $this->assertSame($collection, $this->plugin->afterGetBaseCollection($this->subject, $collection));
    }

    /**
     * Build a fulltext collection whose select carries the given limit parts.
     *
     * @param int|null $count  SQL limit count.
     * @param int|null $offset SQL limit offset.
     *
     * @return Collection&MockObject
     */
    private function createSearchCollection(?int $count, ?int $offset)
    {
        $select = $this->createStub(Select::class);
        $select->method('getPart')->willReturnMap(
            [
                [Select::LIMIT_COUNT, $count],
                [Select::LIMIT_OFFSET, $offset],
            ]
        );

        $collection = $this->createMock(Collection::class);
        $collection->method('getSelect')->willReturn($select);

        return $collection;
    }
}
