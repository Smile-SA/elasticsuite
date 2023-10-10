<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Test\Unit\Model\Optimizer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierListFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\ResultsBuilder;
use Magento\Search\Model\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Functions\ProviderFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration;

/**
 * Optimiser Preview unit testing.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PreviewTest extends TestCase
{
    /**
     * @var Preview\ItemFactory
     */
    private $previewItemFactory;

    /**
     * @var Optimizer
     */
    private $optimizer;

    /**
     * @var ContainerConfiguration
     */
    private $containerConfiguration;

    /**
     * @var CategoryInterface
     */
    private $category;

    /**
     * @var ProviderFactory
     */
    private $providerFactory;

    /**
     * @var ResultsBuilder
     */
    private $previewResultsBuilder;

    /**
     * @var Preview
     */
    private $preview;

    /**
     * @var ApplierListFactory
     */
    private $applierListFactory;

    /**
     * @var QueryInterface
     */
    private $searchQuery;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->optimizer              = $this->getOptimizerMock();
        $this->previewItemFactory     = $this->getPreviewItemFactoryMock();
        $this->applierListFactory     = $this->getApplierListFactoryMock();
        $this->providerFactory        = $this->getProviderFactoryMock();
        $this->containerConfiguration = $this->getContainerConfigMock();
        $this->previewResultsBuilder  = $this->getResultsBuilderMock();
        $this->category               = $this->getCategoryMock();
        $this->searchQuery            = $this->getQueryMock();
        $this->searchContext          = $this->getSearchContextMock();
        $this->preview = new Preview(
            $this->optimizer,
            $this->previewItemFactory,
            $this->applierListFactory,
            $this->providerFactory,
            $this->containerConfiguration,
            $this->previewResultsBuilder,
            $this->searchContext,
            $this->category
        );
    }

    /**
     * Test can apply.
     *
     * @return void.
     * @dataProvider dataProvider
     *
     * @param array   $searchContainers     Search Containers
     * @param array   $name                 Container Name
     * @param array   $quickSearchContainer Quick Search Container
     * @param array   $catalogViewContainer Catalog View Container
     * @param string  $queryText            Query Text
     * @param integer $queryId              Query Id
     * @param integer $category             Category
     * @param boolean $expectedResult       Expected result
     */
    public function testCanApply(
        $searchContainers,
        $name,
        $quickSearchContainer,
        $catalogViewContainer,
        $queryText,
        $queryId,
        $category,
        $expectedResult
    ) : void {
        $class = new ReflectionClass(Preview::class);
        $method = $class->getMethod('canApply');
        $method->setAccessible(true);

        $this->optimizer->method('getSearchContainer')->willReturn($searchContainers);
        $this->optimizer->method('getQuickSearchContainer')->willReturn($quickSearchContainer);
        $this->optimizer->method('getCatalogViewContainer')->willReturn($catalogViewContainer);
        $this->searchQuery->method('getId')->willReturn($queryId);
        $this->searchContext->method('getCurrentSearchQuery')->willReturn($this->searchQuery);
        $this->category->method('getId')->willReturn($category);
        $this->containerConfiguration->method('getName')->willReturn($name);

        $reflection = new ReflectionClass($this->preview);
        $property = $reflection->getProperty('queryText');
        $property->setAccessible(true);
        $property->setValue($this->preview, $queryText);

        $result = $method->invoke($this->preview);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Can apply test data provider.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): array
    {
        $data = [
            [['quick_search_container'], 'catalog_product_autocomplete', null, null, null, null, null, false],
            [
                ['catalog_view_container', 'catalog_product_autocomplete'],
                'catalog_product_autocomplete',
                null,
                null,
                null,
                null,
                null,
                true,
            ],
            [
                ['catalog_view_container'],
                'catalog_product_autocomplete',
                null,
                null,
                null,
                null,
                null,
                false,
            ],
            [null, 'quick_search_container', null, null, null, null, null, false],
            [[], 'quick_search_container', null, null, null, null, null, false],
            [['quick_search_container'], 'quick_search_container', null, null, null, null, null, true],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'quick_search_container',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7,
                        3,
                        9,
                    ],
                ],
                'skirt',
                2,
                3,
                true,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'quick_search_container',
                [
                    'apply_to' => 0,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'skirt',
                2,
                3,
                true,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'catalog_view_container',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 0,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'skirt',
                2,
                3,
                true,
            ],
            [
                ['catalog_view_container', 'catalog_product_autocomplete'],
                'catalog_view_container',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'skirt',
                2,
                3,
                true,
            ],
            [
                ['catalog_view_container'],
                'catalog_view_container',
                null,
                [
                    'apply_to' => 0,
                ],
                null,
                null,
                3,
                true,
            ],
            [
                ['catalog_view_container', 'catalog_product_autocomplete'],
                'catalog_view_container',
                null,
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                null,
                null,
                4,
                false,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'quick_search_container',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'jacket',
                8,
                3,
                false,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'quick_search_container',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'jacket',
                8,
                4,
                false,
            ],
            [
                ['catalog_view_container', 'quick_search_container'],
                'catalog_product_autocomplete',
                [
                    'apply_to' => 1,
                ],
                [
                    'apply_to' => 1,
                    'category_ids' => [
                        7, 3, 9,
                    ],
                ],
                'skirt',
                2,
                4,
                false,
            ],
            [
                ['catalog_view_container', 'quick_search_container'],
                'catalog_product_autocomplete',
                [
                    'apply_to' => 1,
                    'query_ids' => [
                        [
                            'id' => 1,
                            'query_text' => 'watch',
                        ],
                        [
                            'id' => 2,
                            'query_text' => 'skirt',
                        ],
                    ],
                ],
                [
                    'apply_to' => 1,
                ],
                'skirt',
                2,
                4,
                false,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'quick_search_container',
                [
                    'apply_to' => 1,
                ],
                null,
                'skirt',
                2,
                null,
                true,
            ],
            [
                ['catalog_view_container', 'quick_search_container', 'catalog_product_autocomplete'],
                'catalog_view_container',
                null,
                [
                    'apply_to' => 1,
                ],
                null,
                null,
                3,
                true,
            ],
        ];

        return $data;
    }

    /**
     * Generate results builder mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getResultsBuilderMock() : MockObject
    {
        return $this->getMockBuilder(ResultsBuilder::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * Generate container config mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getContainerConfigMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(ContainerConfigurationInterface::class)
            ->getMock();
    }

    /**
     * Generate category mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getCategoryMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder(CategoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Generate optimizer mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getOptimizerMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder(Optimizer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSearchContainer', 'getQuickSearchContainer', 'getCatalogViewContainer'])
            ->getMock();
    }

    /**
     * Generate item factory mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getPreviewItemFactoryMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder('\Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Preview\ItemFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Generate applier list factory mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getApplierListFactoryMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder('\Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\ApplierListFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Generate provider factory mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getProviderFactoryMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder(ProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Generate query mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueryMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this
            ->getMockBuilder(\Magento\Search\Model\Query::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Generate search context mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getSearchContextMock(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockBuilder(ContextInterface::class)->getMock();
    }
}
