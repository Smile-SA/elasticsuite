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

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryCache;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteVirtualCategory\Api\Rule\BuildLimitInterface;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;
use Smile\ElasticsuiteVirtualCategory\Model\Rule;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\BuildAbortedException;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\BuildScope;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\DependencyCollector;

/**
 * Unit test for the build limit of the query of a category requested out of any other query build.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
#[AllowMockObjectsWithoutExpectations]
class RuleBuildLimitTest extends TestCase
{
    /**
     * @var BuildLimitInterface|MockObject
     */
    private $buildLimit;

    /**
     * @var QueryCache|MockObject
     */
    private $queryCache;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var QueryInterface
     */
    private $noResultsQuery;

    /**
     * @var array
     */
    private $createdQueries = [];

    /**
     * An aborted build returns no products, named as such, cached for the abort lifetime, tagged with the categories involved, and
     * is logged.
     *
     * @return void
     */
    public function testAbortedBuild(): void
    {
        $rule = $this->getRule(false);
        $this->buildLimit->expects($this->once())->method('start')->with(1);
        $this->buildLimit->expects($this->once())->method('beforeBuild')->with(42)
            ->willThrowException(new BuildAbortedException('Too many builds.', 600, [7, 8]));
        $this->buildLimit->expects($this->once())->method('stop');
        $this->logger->expects($this->once())->method('error')->with($this->callback(function ($message) {
            return strpos($message, 'category 42 (store 1)') !== false && strpos($message, 'Too many builds.') !== false;
        }));
        $this->queryCache->expects($this->once())->method('save')->with(
            $this->anything(),
            ['query' => $this->noResultsQuery, 'rootPathIds' => [], 'embedded' => [42]],
            ['ES_VCAT_42', 'ES_VCAT_7', 'ES_VCAT_8'],
            $this->callback(function ($expiresAt) {
                return abs($expiresAt - (time() + 600)) <= 5;
            }),
            [],
            true
        );

        $this->assertSame($this->noResultsQuery, $rule->getCategorySearchQuery(42));
        $this->assertSame(
            [
                QueryInterface::TYPE_TERMS,
                [
                    'field'  => 'entity_id',
                    'values' => [0],
                    'name'   => 'category 42 => ' . BuildLimitInterface::ABORTED_QUERY_NAME,
                ],
            ],
            end($this->createdQueries)
        );
    }

    /**
     * A query served from the cache is not submitted to the build limit.
     *
     * @return void
     */
    public function testCachedQuery(): void
    {
        $rule = $this->getRule(true);
        $this->buildLimit->expects($this->once())->method('start');
        $this->buildLimit->expects($this->never())->method('beforeBuild');
        $this->buildLimit->expects($this->once())->method('stop');

        $this->assertNotSame($this->noResultsQuery, $rule->getCategorySearchQuery(42));
    }

    /**
     * Rule of the store 1 whose dependencies are mocked.
     *
     * @param bool $isCached Whether the category query is cached.
     *
     * @return Rule
     */
    private function getRule(bool $isCached): Rule
    {
        $this->buildLimit     = $this->createMock(BuildLimitInterface::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->noResultsQuery = $this->createMock(QueryInterface::class);
        $queryFactory         = $this->createMock(QueryFactory::class);
        $queryFactory->method('create')->willReturnCallback(function ($type, $params) {
            $this->createdQueries[] = [$type, $params];

            return $this->noResultsQuery;
        });

        $this->queryCache = $this->createMock(QueryCache::class);
        $cacheData        = [
            'data'      => ['query' => $this->createMock(QueryInterface::class), 'rootPathIds' => [], 'embedded' => [42]],
            'tags'      => ['ES_VCAT_42'],
            'expiresAt' => null,
            'context'   => [],
        ];
        $this->queryCache->method('load')->willReturn($isCached ? $cacheData : false);

        /** @var Rule|MockObject $rule */
        $rule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $rule->setData('store_id', 1);
        $properties = [
            'config'              => $this->createMock(Config::class),
            'queryFactory'        => $queryFactory,
            'queryCache'          => $this->queryCache,
            'dependencyCollector' => new DependencyCollector(),
            'buildScope'          => new BuildScope($this->buildLimit),
        ];
        foreach ($properties as $property => $value) {
            $reflection = new \ReflectionProperty(Rule::class, $property);
            $reflection->setValue($rule, $value);
        }
        $logger = new \ReflectionProperty(AbstractModel::class, '_logger');
        $logger->setValue($rule, $this->logger);

        return $rule;
    }
}
