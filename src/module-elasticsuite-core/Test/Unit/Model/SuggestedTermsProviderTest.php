<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Model\Autocomplete\SuggestedTermsProvider;
use Smile\ElasticsuiteCore\Helper\Autocomplete;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Smile\ElasticsuiteCore\Model\Search\QueryStringProvider;
use Magento\Search\Model\Autocomplete\Item as TermItem;

/**
 * Search API unit testing.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 */
class SuggestedTermsProviderTest extends TestCase
{
    /**
     * @var (\object&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $helperMock;

    /**
     * @var (\object&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $termDataProviderMock;

    /**
     * @var (\object&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $queryStringProviderFactoryMock;

    /**
     * @var (\object&\PHPUnit\Framework\MockObject\MockObject)
     */
    private $suggestedTermsProvider;

    /**
     * Test setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->helperMock                     = $this->createMock(Autocomplete::class);
        $this->termDataProviderMock           = $this->createMock(TermDataProvider::class);
        $this->queryStringProviderFactoryMock = $this->getMockBuilder(QueryStringProvider::class. 'Factory')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->suggestedTermsProvider = new SuggestedTermsProvider(
            $this->helperMock,
            $this->termDataProviderMock,
            $this->queryStringProviderFactoryMock
        );
    }

    /**
     * Test case when extension is enabled and no limit on the number of suggested terms.
     * It should return all available terms.
     */
    public function testGetSuggestedTermsWithExtensionEnabledAndNoLimit()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(false);

        // Create mock TermItems with terms starting with 'top'.
        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        $expectedTerms = ['top', 'top blouse', 'top tank'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is enabled, and results are limited to one term.
     * It should return only the first term.
     */
    public function testGetSuggestedTermsWithLimitedResultsOneTerm()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(true);
        $this->helperMock->method('getExtensionSize')->willReturn(1);

        // Create mock TermItems with terms starting with 'top'.
        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        // Expect the terms to be limited to only one item.
        $expectedTerms = ['top'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is enabled, and results are limited to two terms.
     * It should return the first two terms.
     */
    public function testGetSuggestedTermsWithLimitedResultsTwoTerms()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(true);
        $this->helperMock->method('getExtensionSize')->willReturn(2);

        // Create mock TermItems with terms starting with 'top'.
        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        $expectedTerms = ['top', 'top blouse'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is enabled, and results are limited to three terms.
     * It should return the first three terms.
     */
    public function testGetSuggestedTermsWithLimitedResultsThreeTerms()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(true);
        $this->helperMock->method('getExtensionSize')->willReturn(3);

        // Create mock TermItems with terms starting with 'top'.
        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        $expectedTerms = ['top', 'top blouse', 'top tank'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is enabled, and limit is greater than the number of available terms.
     * It should return all available terms.
     */
    public function testGetSuggestedTermsWithLimitedResultsLessThanSize()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(true);
        // Limit set to 5, but only 3 items are available.
        $this->helperMock->method('getExtensionSize')->willReturn(5);

        // Create mock TermItems with terms starting with 'top'.
        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        $expectedTerms = ['top', 'top blouse', 'top tank'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is disabled.
     * It should return the query string as the term.
     */
    public function testGetSuggestedTermsWhenExtensionDisabled()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(false);

        $queryStringProviderMock = $this->createMock(\Smile\ElasticsuiteCore\Model\Search\QueryStringProvider::class);
        $queryStringProviderMock->method('get')->willReturn('top');
        $this->queryStringProviderFactoryMock->method('create')->willReturn($queryStringProviderMock);

        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $termItem3 = $this->createMock(TermItem::class);
        $termItem3->method('getTitle')->willReturn('top tank');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2, $termItem3]);

        // Expect the terms to be the query string since the extension is disabled.
        $expectedTerms = ['top'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Test case when extension is enabled and stopped on match.
     * It should return only the term that matches the query string.
     */
    public function testGetSuggestedTermsWithExtensionStoppedOnMatch()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(true);
        $this->helperMock->method('isExtensionLimited')->willReturn(false);

        $queryStringProviderMock = $this->createMock(\Smile\ElasticsuiteCore\Model\Search\QueryStringProvider::class);
        $queryStringProviderMock->method('get')->willReturn('top');
        $this->queryStringProviderFactoryMock->method('create')->willReturn($queryStringProviderMock);

        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2]);

        // Expect the terms to be only 'top' because of the stop on match logic.
        $expectedTerms = ['top'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Tests the scenario where the base query is preserved and added to the suggestions.
     *
     * @return void
     */
    public function testGetSuggestedTermsWithBaseQueryPreserved()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(false);
        $this->helperMock->method('isPreservingBaseQuery')->willReturn(true);

        $queryStringProviderMock = $this->createMock(QueryStringProvider::class);
        $queryStringProviderMock->method('get')->willReturn('to');
        $this->queryStringProviderFactoryMock->method('create')->willReturn($queryStringProviderMock);

        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2]);

        $expectedTerms = ['to', 'top', 'top blouse'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }

    /**
     * Tests the case where the base query matches one of the suggested terms, ensuring duplicates are removed.
     *
     * @return void
     */
    public function testGetSuggestedTermsWithBaseQueryPreservedAndDuplicate()
    {
        $this->helperMock->method('isExtensionEnabled')->willReturn(true);
        $this->helperMock->method('isExtensionStoppedOnMatch')->willReturn(false);
        $this->helperMock->method('isExtensionLimited')->willReturn(false);
        $this->helperMock->method('isPreservingBaseQuery')->willReturn(true);

        $queryStringProviderMock = $this->createMock(QueryStringProvider::class);
        $queryStringProviderMock->method('get')->willReturn('top');
        $this->queryStringProviderFactoryMock->method('create')->willReturn($queryStringProviderMock);

        $termItem1 = $this->createMock(TermItem::class);
        $termItem1->method('getTitle')->willReturn('top');

        $termItem2 = $this->createMock(TermItem::class);
        $termItem2->method('getTitle')->willReturn('top blouse');

        $this->termDataProviderMock->method('getItems')->willReturn([$termItem1, $termItem2]);

        $expectedTerms = ['top', 'top blouse'];

        $terms = $this->suggestedTermsProvider->getSuggestedTerms();

        $this->assertEquals($expectedTerms, $terms);
    }
}
