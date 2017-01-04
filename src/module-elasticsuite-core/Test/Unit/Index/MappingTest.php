<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Index;

use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\DynamicFieldProviderInterface;

/**
 * Mapping test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MappingTest extends \PHPUnit_Framework_TestCase
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
    protected function setUp()
    {
        // Static fields.
        $fields = [
            new Field('entity_id', FieldInterface::FIELD_TYPE_INTEGER),
            new Field('nested.child1', FieldInterface::FIELD_TYPE_STRING, 'nested'),
            new Field('nested.child2', FieldInterface::FIELD_TYPE_STRING, 'nested'),
            new Field('object.child1', FieldInterface::FIELD_TYPE_STRING),
            new Field('object.child2', FieldInterface::FIELD_TYPE_STRING),
        ];

        // Stubing a dynanyc data provider.
        $dynamicDataProvider       = $this->getMock(DynamicFieldProviderInterface::class);
        $dynamicDataProviderFields = [
            new Field('title', FieldInterface::FIELD_TYPE_STRING, null, ['is_searchable' => true]),
        ];
        $dynamicDataProvider->method('getFields')
            ->will($this->returnValue($dynamicDataProviderFields));

        // Create a mapping.
        $this->mapping = new Mapping('entity_id', $fields, [$dynamicDataProvider]);
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
        $this->assertEquals(FieldInterface::FIELD_TYPE_MULTI, $properties[Mapping::DEFAULT_SEARCH_FIELD]['type']);
        $this->assertArrayHasKey(Mapping::DEFAULT_SEARCH_FIELD, $properties[Mapping::DEFAULT_SEARCH_FIELD]['fields']);
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
        $this->assertEquals(FieldInterface::FIELD_TYPE_MULTI, $properties[Mapping::DEFAULT_AUTOCOMPLETE_FIELD]['type']);
        $this->assertArrayHasKey(Mapping::DEFAULT_AUTOCOMPLETE_FIELD, $properties[Mapping::DEFAULT_AUTOCOMPLETE_FIELD]['fields']);
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
        $this->assertEquals(FieldInterface::FIELD_TYPE_MULTI, $properties[Mapping::DEFAULT_SPELLING_FIELD]['type']);
        $this->assertArrayHasKey(Mapping::DEFAULT_SPELLING_FIELD, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_WHITESPACE, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_SHINGLE, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_PHONETIC, $properties[Mapping::DEFAULT_SPELLING_FIELD]['fields']);
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

        $this->assertCount(6, $fields);
        $this->assertCount(7, $properties);

        $this->assertEquals('entity_id', $this->mapping->getIdField()->getName());
        $this->assertArrayHasKey('entity_id', $fields);

        $this->assertArrayHasKey('title', $fields);
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
     * @expectedException        \LogicException
     * @expectedExceptionMessage Field invalidField does not exists in mapping
     *
     * @return void
     */
    public function testMissingFieldAccess()
    {
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
        $this->assertArrayHasKey('_all', $mapping);
        $this->assertArrayHasKey('properties', $mapping);
        $this->assertCount(7, $mapping['properties']);
    }

    /**
     * Test an exception is raised when using an invalid id field as id.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid id field missingField : field is not declared.
     *
     * @return void
     */
    public function testInvalidIdField()
    {
        new Mapping('missingField');
    }
}
