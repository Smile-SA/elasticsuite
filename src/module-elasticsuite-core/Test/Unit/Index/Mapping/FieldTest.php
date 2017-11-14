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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Mapping;

use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Mapping field test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FieldTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test default values used when creating a new field without params.
     *
     * @return void
     */
    public function testDefaultValues()
    {
        $field = new Field('fieldName');

        $this->assertEquals('fieldName', $field->getName());
        $this->assertEquals(FieldInterface::FIELD_TYPE_STRING, $field->getType());
        $this->assertEquals(false, $field->isSearchable());
        $this->assertEquals(1, $field->getSearchWeight());
        $this->assertEquals(true, $field->isFilterable());
        $this->assertEquals(false, $field->isUsedForSortBy());
        $this->assertEquals(false, $field->isUsedInSpellcheck());
        $this->assertEquals(false, $field->isNested());
        $this->assertEquals(null, $field->getNestedFieldName());
        $this->assertEquals(null, $field->getNestedPath());

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_STRING, $mappingPropertyConfig['type']);
        $this->assertEquals('fieldName', $field->getMappingProperty());
        $this->assertEquals(null, $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD));
    }

    /**
     * Test nested field configuration.
     *
     * @return void
     */
    public function testNestedField()
    {
        $field = new Field('parent.child', FieldInterface::FIELD_TYPE_STRING, 'parent', ['is_searchable' => true]);

        $this->assertEquals(true, $field->isNested());
        $this->assertEquals('parent', $field->getNestedPath());
        $this->assertEquals('child', $field->getNestedFieldName());

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertArrayHasKey('child', $mappingPropertyConfig['fields']);
    }

    /**
     * Test invalid nested field configuration.
     *
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Invalid nested path or field name
     *
     * @return void
     */
    public function testInvalidNestedField()
    {
        new Field('parent.child', FieldInterface::FIELD_TYPE_STRING, 'invalidparent');
    }

    /**
     * Test basic type mapping generation.
     *
     * @return void
     */
    public function testBasicTypes()
    {
        $types = [
            FieldInterface::FIELD_TYPE_INTEGER, FieldInterface::FIELD_TYPE_DOUBLE,
            FieldInterface::FIELD_TYPE_BOOLEAN, FieldInterface::FIELD_TYPE_DATE,
        ];

        foreach ($types as $type) {
            $field                 = new Field('field', $type);
            $this->assertEquals($type, $field->getType());

            $mappingPropertyConfig = $field->getMappingPropertyConfig();
            $this->assertEquals($type, $mappingPropertyConfig['type']);
            $this->assertEquals(true, $mappingPropertyConfig['doc_values']);

            if ($type === FieldInterface::FIELD_TYPE_DATE) {
                $this->assertEquals('yyyy-MM-dd HH:mm:ss||yyyy-MM-dd', $mappingPropertyConfig['format']);
            }

            $this->assertEquals('field', $field->getMappingProperty());
        }
    }

    /**
     * Test complex searchable string fields mapping generation.
     *
     * @return void
     */
    public function testComplexSearchableStringField()
    {
        $fieldConfig = ['is_searchable' => true, 'is_used_for_sort_by' => true, 'search_weight' => 2];
        $fieldType   = FieldInterface::FIELD_TYPE_STRING;
        $field       = new Field('field', $fieldType, null, $fieldConfig);

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_STRING, $mappingPropertyConfig['type']);

        $this->assertEquals(FieldInterface::ANALYZER_STANDARD, $mappingPropertyConfig['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_WHITESPACE, $mappingPropertyConfig['fields']['whitespace']['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_SHINGLE, $mappingPropertyConfig['fields']['shingle']['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_SORTABLE, $mappingPropertyConfig['fields']['sortable']['analyzer']);

        $this->assertEquals('field', $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD));
        $this->assertEquals('field.whitespace', $field->getMappingProperty(FieldInterface::ANALYZER_WHITESPACE));
        $this->assertEquals('field.shingle', $field->getMappingProperty(FieldInterface::ANALYZER_SHINGLE));
        $this->assertEquals('field.sortable', $field->getMappingProperty(FieldInterface::ANALYZER_SORTABLE));
        $this->assertEquals('field.untouched', $field->getMappingProperty());
    }

    /**
     * Test simple searchable string fields mapping generation.
     *
     * @return void
     */
    public function testSimpleSearchableStringField()
    {
        $fieldConfig = ['is_searchable' => true, 'is_filterable' => false, 'search_weight' => 1];
        $fieldType   = FieldInterface::FIELD_TYPE_STRING;
        $field       = new Field('field', $fieldType, null, $fieldConfig);

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_STRING, $mappingPropertyConfig['type']);

        $this->assertEquals(null, $field->getMappingProperty());
        $this->assertEquals('field', $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD));
    }
}
