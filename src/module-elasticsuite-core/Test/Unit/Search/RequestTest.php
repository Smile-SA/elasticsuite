<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Search;

use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\Request\QueryInterface;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Default implementation of ElasticSuite search request test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestTest extends TestCase
{
    /**
     * Tests that the Request constructor stores all provided parameters correctly.
     *
     * Verifies that when a Request object is instantiated with all parameters,
     * the getter methods return the exact same values that were passed in.
     *
     * @return void
     */
    public function testConstructWithAllParametersStoresValuesCorrectly()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $filter = $this->createMock(QueryInterface::class);
        $sortOrders = [$this->createMock(SortOrderInterface::class)];
        $from = 10;
        $size = 20;
        $dimensions = [$this->createMock(Dimension::class)];
        $buckets = [$this->createMock(BucketInterface::class)];
        $spellingType = 'fuzzy_spelling';
        $trackTotalHits = 5000;
        $minScore = 0.5;

        $request = new Request(
            $name,
            $indexName,
            $query,
            $filter,
            $sortOrders,
            $from,
            $size,
            $dimensions,
            $buckets,
            $spellingType,
            $trackTotalHits,
            $minScore
        );

        $this->assertSame($filter, $request->getFilter());
        $this->assertSame($sortOrders, $request->getSortOrders());
        $this->assertSame($trackTotalHits, $request->getTrackTotalHits());
        $this->assertSame($minScore, $request->getMinScore());
    }

    /**
     * Tests that the Request constructor uses default values for optional parameters.
     *
     * Verifies that when a Request object is instantiated with only required parameters,
     * optional parameters default to null or false as appropriate.
     *
     * @return void
     */
    public function testConstructWithOnlyRequiredParametersUsesDefaultValues()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);

        $request = new Request(
            $name,
            $indexName,
            $query
        );

        $this->assertNull($request->getFilter());
        $this->assertNull($request->getSortOrders());
        $this->assertNull($request->getMinScore());
        $this->assertFalse($request->hasCollapse());
        $this->assertFalse($request->hasSourceConfig());
    }

    /**
     * Tests that the Request constructor uses the default value for trackTotalHits when not provided.
     *
     * Verifies that when trackTotalHits is not specified, it defaults to
     * PER_SHARD_MAX_RESULT_WINDOW from IndexSettings.
     *
     * @return void
     */
    public function testConstructWithoutTrackTotalHitsUsesDefaultValue()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);

        $request = new Request(
            $name,
            $indexName,
            $query
        );

        $this->assertEquals(
            \Smile\ElasticsuiteCore\Helper\IndexSettings::PER_SHARD_MAX_RESULT_WINDOW,
            $request->getTrackTotalHits()
        );
    }

    /**
     * Tests that numeric string values for trackTotalHits are converted to integers.
     *
     * Verifies that when trackTotalHits is provided as a numeric string,
     * it is properly parsed and converted to an integer type.
     *
     * @return void
     */
    public function testParseTrackTotalHitsWithNumericStringConvertsToInteger()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $trackTotalHitsString = '5000';

        $request = new Request(
            $name,
            $indexName,
            $query,
            null,
            null,
            null,
            null,
            [],
            [],
            null,
            $trackTotalHitsString
        );

        $this->assertIsInt($request->getTrackTotalHits());
        $this->assertEquals(5000, $request->getTrackTotalHits());
    }

    /**
     * Tests that boolean string values for trackTotalHits are converted correctly.
     *
     * Verifies that when trackTotalHits is provided as the string 'true',
     * it is properly parsed and converted to a boolean true value.
     *
     * @return void
     */
    public function testParseTrackTotalHitsWithBooleanStringConvertsCorrectly()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $trackTotalHitsString = 'true';

        $request = new Request(
            $name,
            $indexName,
            $query,
            null,
            null,
            null,
            null,
            [],
            [],
            null,
            $trackTotalHitsString
        );

        $this->assertTrue($request->getTrackTotalHits());
    }

    /**
     * Tests that a false value for trackTotalHits is converted to zero.
     *
     * Verifies that when trackTotalHits is provided as boolean false,
     * it is converted to the integer value 0.
     *
     * @return void
     */
    public function testParseTrackTotalHitsWithFalseReturnsZero()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $trackTotalHitsFalse = false;

        $request = new Request(
            $name,
            $indexName,
            $query,
            null,
            null,
            null,
            null,
            [],
            [],
            null,
            $trackTotalHitsFalse
        );

        $this->assertEquals(0, $request->getTrackTotalHits());
    }

    /**
     * Tests that isSpellchecked returns true for fuzzy spelling type.
     *
     * Verifies that when a Request is created with the SPELLING_TYPE_FUZZY spelling type,
     * the isSpellchecked method returns true.
     *
     * @return void
     */
    public function testIsSpellcheckedReturnsTrueForFuzzySpellingType()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_FUZZY;

        $request = new Request(
            $name,
            $indexName,
            $query,
            null,
            null,
            null,
            null,
            [],
            [],
            $spellingType
        );

        $this->assertTrue($request->isSpellchecked());
    }

    /**
     * Tests that isSpellchecked returns true for most fuzzy spelling type.
     *
     * Verifies that when a Request is created with the SPELLING_TYPE_MOST_FUZZY spelling type,
     * the isSpellchecked method returns true.
     *
     * @return void
     */
    public function testIsSpellcheckedReturnsTrueForMostFuzzySpellingType()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $spellingType = SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY;

        $request = new Request(
            $name,
            $indexName,
            $query,
            null,
            null,
            null,
            null,
            [],
            [],
            $spellingType
        );

        $this->assertTrue($request->isSpellchecked());
    }

    /**
     * Tests that setSourceConfig stores the value and returns the Request instance.
     *
     * Verifies that the setSourceConfig method properly stores the provided source configuration
     * and returns the Request instance itself for method chaining.
     *
     * @param mixed $sourceConfig The source configuration value to set (null, empty array, or array of field names).
     *
     * @return void
     *
     * @dataProvider sourceConfigDataProvider
     */
    public function testSetSourceConfigStoresValueAndReturnsInstance($sourceConfig)
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);

        $request = new Request(
            $name,
            $indexName,
            $query
        );

        $result = $request->setSourceConfig($sourceConfig);

        $this->assertSame($sourceConfig, $request->getSourceConfig());
        $this->assertSame($request, $result);
    }

    /**
     * Provides test data for source configuration testing.
     *
     * Returns an array of test cases with different source configuration values:
     * null, empty array, and non-empty array of field names.
     *
     * @return array An associative array of test cases with descriptive keys and configuration values.
     */
    public function sourceConfigDataProvider()
    {
        return [
            'null_value' => [null],
            'empty_array' => [[]],
            'non_empty_array' => [['field1', 'field2', 'field3']],
        ];
    }

    /**
     * Tests that getSourceConfig returns an empty array when source config is never set.
     *
     * Verifies that when a Request is created without setting source configuration,
     * getSourceConfig returns an empty array rather than null.
     *
     * @return void
     */
    public function testGetSourceConfigReturnsEmptyArrayWhenNeverSet()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);

        $request = new Request(
            $name,
            $indexName,
            $query
        );

        $this->assertIsArray($request->getSourceConfig());
        $this->assertEmpty($request->getSourceConfig());
    }

    /**
     * Tests that getSourceConfig and hasSourceConfig work together correctly.
     *
     * Verifies that hasSourceConfig returns false and getSourceConfig returns an empty array
     * initially, and after setting source configuration, both methods reflect the change appropriately.
     *
     * @return void
     */
    public function testGetSourceConfigAndHasSourceConfigWorkTogether()
    {
        $name = 'test_request';
        $indexName = 'test_index';
        $query = $this->createMock(QueryInterface::class);
        $sourceConfig = ['field1', 'field2'];

        $request = new Request(
            $name,
            $indexName,
            $query
        );

        // Initially, hasSourceConfig should return false and getSourceConfig should return empty array.
        $this->assertFalse($request->hasSourceConfig());
        $this->assertEmpty($request->getSourceConfig());

        // After setting source config, both methods should reflect the change.
        $request->setSourceConfig($sourceConfig);
        $this->assertTrue($request->hasSourceConfig());
        $this->assertSame($sourceConfig, $request->getSourceConfig());
    }
}
