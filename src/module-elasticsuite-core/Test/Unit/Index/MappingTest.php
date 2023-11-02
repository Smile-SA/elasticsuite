<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index;

use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Fulltext\SearchableFieldFilter;

/**
 * Mapping test case.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticsuiteCore\Index\Mapping
     */
    private $mapping;

    /**
     * Create a mapping with some fields to run the tests.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        // Static fields.
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('nested.child1', FieldInterface::FIELD_TYPE_TEXT, 'nested'),
            new Field('nested.child2', FieldInterface::FIELD_TYPE_TEXT, 'nested'),
            new Field('object.child1', FieldInterface::FIELD_TYPE_TEXT),
            new Field('object.child2', FieldInterface::FIELD_TYPE_TEXT),
        ];

        // Create a mapping.
        $this->mapping = new Mapping('entity_id', $fields);
    }

    /**
     * Test the search field mapping generation is correct.
     *
     * @return void
     */
    public function testDefaultSearchProperty()
    {
        $properties = $this->mapping->getProperties();

        $this->assertArrayHasKey(Mapping::DEFAULT_SEARCH_FIELD, $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $properties[Mapping::DEFAULT_SEARCH_FIELD]['type']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_SEARCH_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_SEARCH_FIELD]['fields']);
    }

    /**
     * Test the autocomplete field mapping generation is correct.
     *
     * @return void
     */
    public function testDefaultAutocompleteProperty()
    {
        $properties = $this->mapping->getProperties();

        $this->assertArrayHasKey(Mapping::DEFAULT_AUTOCOMPLETE_FIELD, $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $properties[Mapping::DEFAULT_AUTOCOMPLETE_FIELD]['type']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_AUTOCOMPLETE_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_AUTOCOMPLETE_FIELD]['fields']);
    }

    /**
     * Test the spelling field mapping generation is correct.
     *
     * @return void
     */
    public function testDefaultSpellingProperty()
    {
        $properties = $this->mapping->getProperties();

        $this->assertArrayHasKey(Mapping::DEFAULT_SPELLING_FIELD, $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $properties[Mapping::DEFAULT_SPELLING_FIELD]['type']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_PHONETIC, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
    }

    /**
     * Test the reference collector field mapping generation is correct.
     *
     * @return void
     */
    public function testReferenceProperty()
    {
        $properties = $this->mapping->getProperties();

        $this->assertArrayHasKey(Mapping::DEFAULT_REFERENCE_FIELD, $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $properties[Mapping::DEFAULT_REFERENCE_FIELD]['type']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_REFERENCE, $properties[Mapping::DEFAULT_REFERENCE_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_REFERENCE_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_REFERENCE_FIELD]['fields']);
    }

    /**
     * Test the edge_ngram collector field mapping generation is correct.
     *
     * @return void
     */
    public function testEdgeNgramProperty()
    {
        $properties = $this->mapping->getProperties();

        $this->assertArrayHasKey(Mapping::DEFAULT_EDGE_NGRAM_FIELD, $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $properties[Mapping::DEFAULT_EDGE_NGRAM_FIELD]['type']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_EDGE_NGRAM, $properties[Mapping::DEFAULT_EDGE_NGRAM_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_EDGE_NGRAM_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_EDGE_NGRAM_FIELD]['fields']);
    }

    /**
     * Test the basic fields mapping generation is correct.
     *
     *  @return void
     */
    public function testBasicFields()
    {
        $fields     = $this->mapping->getFields();
        $properties = $this->mapping->getProperties();

        $this->assertCount(5, $fields);
        $this->assertCount(8, $properties);

        $this->assertEquals('entity_id', $this->mapping->getIdField()->getName());
        $this->assertArrayHasKey('entity_id', $fields);
    }

    /**
     * Test nested fields generaion is correct.
     *
     * @return void
     */
    public function testNestedField()
    {
        $properties = $this->mapping->getProperties();
        $this->assertArrayHasKey('nested', $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_NESTED, $properties['nested']['type']);
        $this->assertArrayHasKey('child1', $properties['nested']['properties']);
        $this->assertArrayHasKey('child2', $properties['nested']['properties']);
    }

    /**
     * Test object fields generaion is correct.
     *
     * @return void
     */
    public function testObjectField()
    {
        $properties = $this->mapping->getProperties();
        $this->assertArrayHasKey('object', $properties);
        $this->assertEquals(FieldInterface::FIELD_TYPE_OBJECT, $properties['object']['type']);
        $this->assertArrayHasKey('child1', $properties['object']['properties']);
        $this->assertArrayHasKey('child2', $properties['object']['properties']);
    }

    /**
     * Test an exception is raised when trying to access a missing field.
     *
     * @return void
     */
    public function testMissingFieldAccess()
    {
        $this->expectExceptionMessage("Field invalidField does not exists in mapping");
        $this->expectException(\LogicException::class);
        $this->mapping->getField('invalidField');
    }

    /**
     * Test the full mapping generation.
     *
     * @return void
     */
    public function testMappingGeneration()
    {
        $mapping = $this->mapping->asArray();
        $this->assertArrayHasKey('properties', $mapping);
        $this->assertCount(8, $mapping['properties']);
    }

    /**
     * Test an exception is raised when using an invalid id field as id.
     *
     * @return void
     */
    public function testInvalidIdField()
    {
        $this->expectExceptionMessage("Invalid id field missingField : field is not declared.");
        $this->expectException(\InvalidArgumentException::class);
        new Mapping('missingField');
    }

    /**
     * Test weighted field generation.
     *
     * @return void
     */
    public function testGetWeightedSearchProperties()
    {
        $mapping     = $this->getSearchWeightedMapping();
        $fieldFilter = new SearchableFieldFilter();

        $properties = $mapping->getWeightedSearchProperties(null, null, 2, $fieldFilter);

        $this->assertCount(4, $properties);
        $this->assertEquals(2, $properties['standardField.standard']);
        $this->assertEquals(4, $properties['weightedField.standard']);
        $this->assertEquals(2, $properties['whitespaceField.whitespace']);
        $this->assertEquals(4, $properties['whitespaceWeightedField.whitespace']);

        $properties = $mapping->getWeightedSearchProperties(Field::ANALYZER_STANDARD, null, 1, $fieldFilter);
        $this->assertCount(2, $properties);
        $this->assertEquals(1, $properties['standardField.standard']);
        $this->assertEquals(2, $properties['weightedField.standard']);

        $properties = $mapping->getWeightedSearchProperties(Field::ANALYZER_WHITESPACE, null, 1, $fieldFilter);
        $this->assertCount(3, $properties);
        $this->assertEquals(2, $properties['weightedField.whitespace']);
        $this->assertEquals(1, $properties['whitespaceField.whitespace']);
        $this->assertEquals(2, $properties['whitespaceWeightedField.whitespace']);

        $properties = $mapping->getWeightedSearchProperties(null, Mapping::DEFAULT_SEARCH_FIELD, 1, $fieldFilter);
        $this->assertCount(4, $properties);
        $this->assertEquals(1, $properties['search']);
        $this->assertEquals(2, $properties['weightedField.standard']);
        $this->assertEquals(1, $properties['whitespaceField.whitespace']);
        $this->assertEquals(2, $properties['whitespaceWeightedField.whitespace']);

        $properties = $mapping->getWeightedSearchProperties(Field::ANALYZER_WHITESPACE, Mapping::DEFAULT_SEARCH_FIELD, 1, $fieldFilter);
        $this->assertCount(3, $properties);
        $this->assertEquals(1, $properties['search.whitespace']);
        $this->assertEquals(2, $properties['weightedField.whitespace']);
        $this->assertEquals(2, $properties['whitespaceWeightedField.whitespace']);
    }

    /**
     * Test an exception is thrown when using an invalid default field.
     *
     * @return void
     */
    public function testInvalidDefaultField()
    {
        $this->expectExceptionMessage("Unable to find field invalidDefaultField.");
        $this->expectException(\InvalidArgumentException::class);
        $this->getSearchWeightedMapping()->getWeightedSearchProperties(null, 'invalidDefaultField');
    }

    /**
     * Test an exception is thrown when using an invalid analyzer.
     *
     * @return void
     */
    public function testInvalidDefaultFieldAnalyzer()
    {
        $this->expectExceptionMessage("Unable to find analyzer invalidAnalyzer for field search.");
        $this->expectException(\InvalidArgumentException::class);
        $this->getSearchWeightedMapping()->getWeightedSearchProperties('invalidAnalyzer', Mapping::DEFAULT_SEARCH_FIELD);
    }

    /**
     * Return mapping used in weighted search field tests.
     *
     * @return \Smile\ElasticsuiteCore\Index\Mapping
     */
    private function getSearchWeightedMapping()
    {
        $fields = [
            new Field(
                'entity_id',
                FieldInterface::FIELD_TYPE_INTEGER
            ),
            new Field(
                'ignoredField',
                FieldInterface::FIELD_TYPE_TEXT
            ),
            new Field(
                'standardField',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => true]
            ),
            new Field(
                'weightedField',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => true, 'search_weight' => 2]
            ),
            new Field(
                'whitespaceField',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => true, 'search_weight' => 1, 'default_search_analyzer' => Field::ANALYZER_WHITESPACE]
            ),
            new Field(
                'whitespaceWeightedField',
                FieldInterface::FIELD_TYPE_TEXT,
                null,
                ['is_searchable' => true, 'search_weight' => 2, 'default_search_analyzer' => Field::ANALYZER_WHITESPACE]
            ),
            new Field(
                'nested.subfield',
                FieldInterface::FIELD_TYPE_TEXT,
                'nested'
            ),
            new Field(
                'object.subfield',
                FieldInterface::FIELD_TYPE_TEXT
            ),
        ];

        return new Mapping('entity_id', $fields);
    }
}
