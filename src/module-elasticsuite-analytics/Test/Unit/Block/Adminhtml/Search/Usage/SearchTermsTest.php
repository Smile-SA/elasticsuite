<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Łukasz Bajsarowicz <lukasz@lbajsarowicz.me>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Test\Unit\Block\Adminhtml\Search\Usage;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DataObject;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Search\Model\ResourceModel\Query as QueryResource;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteAnalytics\Block\Adminhtml\Search\Usage\SearchTerms;

/**
 * Search usage edit links testing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchTermsTest extends TestCase
{
    /**
     * Test batching without changing report rows, missing links or SQL term comparisons.
     *
     * @return void
     */
    public function testTermsUseOneStoreScopedLookup()
    {
        $terms = ['Shoes', 'shoes', 'café', "quote's", ' padded ', 0, '', 'missing'];
        for ($index = 0; $index < 42; $index++) {
            $terms[] = 'term-' . $index;
        }
        $terms[] = 'Shoes';
        $data = [];
        $ids = [];
        $caseVariantIds = ['Shoes' => 1, 'shoes' => 1];
        foreach ($terms as $index => $term) {
            $data['row-' . $index] = ['term' => $term, 'count' => $index + 1];
            $ids['term_' . $index] = $term === 'missing' ? null : ($caseVariantIds[$term] ?? $index + 1);
        }
        $data = ['without-term' => ['count' => 99]] + $data;
        $factory = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $query = $this->createMock(Query::class);
        $factory->expects($this->once())->method('create')->willReturn($query);
        $query->expects($this->never())->method('loadByQueryText');
        $query->expects($this->once())->method('getStoreId')->willReturn(7);
        $resource = $this->createStub(QueryResource::class);
        $query->method('getResource')->willReturn($resource);
        $resource->method('getMainTable')->willReturn('prefix_search_query');
        $connection = $this->createMock(AdapterInterface::class);
        $resource->method('getConnection')->willReturn($connection);
        $select = $this->createMock(Select::class);
        $connection->method('select')->willReturn($select);
        $connection->method('quote')->willReturnCallback(function ($value) {
            $this->assertIsString($value);

            return "'" . str_replace("'", "''", $value) . "'";
        });
        $select->expects($this->once())->method('from')->with(
            'prefix_search_query',
            $this->callback(function ($columns) use ($terms, $connection) {
                foreach ($terms as $index => $term) {
                    $this->assertSame(
                        'MAX(CASE WHEN query_text = ' . $connection->quote((string) $term) . ' THEN query_id END)',
                        (string) $columns['term_' . $index]
                    );
                }

                return count($columns) === count($terms);
            })
        )->willReturnSelf();
        $conditions = [];
        $select->method('where')->willReturnCallback(function ($condition, $value) use (&$conditions, $select) {
            $conditions[$condition] = $value;

            return $select;
        });
        $connection->expects($this->once())->method('fetchRow')->with($select)->willReturn($ids);
        $block = $this->createBlock($factory, new DataObject($data), count($terms) - 1);
        $expected = $data;
        foreach ($expected as $index => &$row) {
            $queryId = $index === 'without-term' ? null : $ids['term_' . substr($index, 4)];
            $row['url'] = $queryId ? 'edit/' . $queryId : null;
        }
        $this->assertSame($expected, $block->getTermsData());
        $this->assertSame(['store_id = ?' => 7, 'query_text IN (?)' => array_map('strval', $terms)], $conditions);
    }

    /**
     * Test large reports resolve all links in batches of at most 100 terms.
     *
     * @return void
     */
    public function testLargeReportsUseBoundedLookups()
    {
        $data = [];
        $expected = [];
        for ($index = 0; $index < 201; $index++) {
            $key = 'row-' . ($index * 2);
            $data[$key] = ['term' => 'term-' . $index, 'count' => $index];
            $expected[$key] = $data[$key] + ['url' => 'edit/' . ($index + 1)];
        }
        $factory = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $query = $this->createMock(Query::class);
        $factory->expects($this->once())->method('create')->willReturn($query);
        $query->expects($this->never())->method('loadByQueryText');
        $query->method('getStoreId')->willReturn(7);
        $resource = $this->createStub(QueryResource::class);
        $query->method('getResource')->willReturn($resource);
        $resource->method('getMainTable')->willReturn('prefix_search_query');
        $connection = $this->createMock(AdapterInterface::class);
        $resource->method('getConnection')->willReturn($connection);
        $connection->method('quote')->willReturnCallback(function ($value) {
            return "'" . $value . "'";
        });
        $batchTerms = [];
        $connection->expects($this->exactly(3))->method('select')->willReturnCallback(function () use (&$batchTerms) {
            $select = $this->createMock(Select::class);
            $select->expects($this->once())->method('from')->with(
                'prefix_search_query',
                $this->callback(function ($columns) {
                    return count($columns) > 0 && count($columns) <= 100;
                })
            )->willReturnSelf();
            $select->method('where')->willReturnCallback(function ($condition, $value) use (&$batchTerms, $select) {
                if ($condition === 'query_text IN (?)') {
                    $batchTerms = $value;
                }
                if ($condition !== 'query_text IN (?)') {
                    $this->assertSame('store_id = ?', $condition);
                    $this->assertSame(7, $value);
                }

                return $select;
            });

            return $select;
        });
        $batchSizes = [];
        $connection->expects($this->exactly(3))->method('fetchRow')->willReturnCallback(
            function () use (&$batchTerms, &$batchSizes) {
                $batchSizes[] = count($batchTerms);
                $result = [];
                foreach (array_values($batchTerms) as $index => $term) {
                    $result['term_' . $index] = (int) substr($term, 5) + 1;
                }

                return $result;
            }
        );
        $this->assertSame($expected, $this->createBlock($factory, new DataObject($data), 201)->getTermsData());
        $this->assertSame([100, 100, 1], $batchSizes);
    }

    /**
     * Test empty and unavailable reports do not access search queries.
     *
     * @return void
     */
    public function testEmptyReportsDoNotLoadQueries()
    {
        $factory = $this->getMockBuilder(QueryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->expects($this->never())->method('create');
        $this->assertSame([], $this->createBlock($factory, new DataObject())->getTermsData());
        $missingTerms = ['row-5' => ['count' => 2]];
        $this->assertSame(
            ['row-5' => ['count' => 2, 'url' => null]],
            $this->createBlock($factory, new DataObject($missingTerms))->getTermsData()
        );
        $report = $this->createStub(DataObject::class);
        $report->method('getData')->willThrowException(new \LogicException('Report unavailable'));
        $this->assertSame([], $this->createBlock($factory, $report)->getTermsData());
    }

    /**
     * Create a block with an isolated report and URL builder.
     *
     * @param QueryFactory $factory Query factory.
     * @param DataObject   $report  Report data.
     * @param int          $links   Expected number of links.
     *
     * @return SearchTerms
     */
    private function createBlock(QueryFactory $factory, DataObject $report, $links = 0)
    {
        $block = $this->getMockBuilder(SearchTerms::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUrl'])
            ->getMock();
        $property = new \ReflectionProperty(SearchTerms::class, 'queryFactory');
        $property->setValue($block, $factory);
        $block->setData('report', $report);
        $block->expects($this->exactly($links))->method('getUrl')->willReturnCallback(function ($route, $params) {
            $this->assertSame('search/term/edit', $route);

            return 'edit/' . $params['id'];
        });

        return $block;
    }
}
