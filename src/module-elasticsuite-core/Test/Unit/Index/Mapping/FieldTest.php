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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Mapping;

use Smile\ElasticsuiteCore\Index\Mapping\Field;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Mapping field test case.
 *
 * @category  Smile
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
        $this->assertEquals(FieldInterface::FIELD_TYPE_KEYWORD, $field->getType());
        $this->assertEquals(false, $field->isSearchable());
        $this->assertEquals(1, $field->getSearchWeight());
        $this->assertEquals(true, $field->isFilterable());
        $this->assertEquals(false, $field->isUsedForSortBy());
        $this->assertEquals(false, $field->isUsedInSpellcheck());
        $this->assertEquals(false, $field->isNested());
        $this->assertEquals(null, $field->getNestedFieldName());
        $this->assertEquals(null, $field->getNestedPath());
        $this->assertEquals('_last', $field->getSortMissing());
        $this->assertEquals('_last', $field->getSortMissing(SortOrderInterface::SORT_ASC));
        $this->assertEquals('_first', $field->getSortMissing(SortOrderInterface::SORT_DESC));

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_KEYWORD, $mappingPropertyConfig['type']);
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
        $field = new Field('parent.child', FieldInterface::FIELD_TYPE_TEXT, 'parent', ['is_searchable' => true]);

        $this->assertEquals(true, $field->isNested());
        $this->assertEquals('parent', $field->getNestedPath());
        $this->assertEquals('child', $field->getNestedFieldName());

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertArrayHasKey('fields', $mappingPropertyConfig);
        $this->assertArrayHasKey(FieldInterface::ANALYZER_UNTOUCHED, $mappingPropertyConfig['fields']);
    }

    /**
     * Test invalid nested field configuration.
     *
     * @return void
     */
    public function testInvalidNestedField()
    {
        $this->expectExceptionMessage("Invalid nested path or field name");
        $this->expectException(\InvalidArgumentException::class);
        new Field('parent.child', FieldInterface::FIELD_TYPE_TEXT, 'invalidparent');
    }

    /**
     * Test basic type mapping generation.
     *
     * @return void
     */
    public function testBasicTypes()
    {
        $types = [
            FieldInterface::FIELD_TYPE_INTEGER,
            FieldInterface::FIELD_TYPE_LONG,
            FieldInterface::FIELD_TYPE_DOUBLE,
            FieldInterface::FIELD_TYPE_BOOLEAN,
            FieldInterface::FIELD_TYPE_DATE,
        ];

        foreach ($types as $type) {
            $field                 = new Field('field', $type);
            $this->assertEquals($type, $field->getType());

            $mappingPropertyConfig = $field->getMappingPropertyConfig();
            $this->assertEquals($type, $mappingPropertyConfig['type']);

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
        $fieldType   = FieldInterface::FIELD_TYPE_TEXT;
        $field       = new Field('field', $fieldType, null, $fieldConfig);

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $mappingPropertyConfig['type']);

        $this->assertEquals(FieldInterface::ANALYZER_KEYWORD, $mappingPropertyConfig['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_WHITESPACE, $mappingPropertyConfig['fields']['whitespace']['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_SHINGLE, $mappingPropertyConfig['fields']['shingle']['analyzer']);
        $this->assertEquals(FieldInterface::ANALYZER_SORTABLE, $mappingPropertyConfig['fields']['sortable']['analyzer']);

        $this->assertEquals('field.standard', $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD));
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
        $fieldType   = FieldInterface::FIELD_TYPE_TEXT;
        $field       = new Field('field', $fieldType, null, $fieldConfig);

        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_TEXT, $mappingPropertyConfig['type']);

        $this->assertEquals(null, $field->getMappingProperty());
        $this->assertEquals('field.standard', $field->getMappingProperty(FieldInterface::ANALYZER_STANDARD));
    }

    /**
     * @dataProvider getIsUsedInSpellcheckFieldConfigDataProvider
     *
     * @param array $fieldConfig        Field configuration from data provider
     * @param bool  $isSearchable       Expected result for is_searchable property
     * @param bool  $isUsedInSpellcheck Expected result for is_used_in_spellcheck property
     */
    public function testIsUsedInSpellcheckField($fieldConfig, $isSearchable, $isUsedInSpellcheck)
    {
        $fieldType   = FieldInterface::FIELD_TYPE_TEXT;
        $field       = new Field('field', $fieldType, null, $fieldConfig);

        $this->assertEquals($isSearchable, $field->isSearchable());
        $this->assertEquals($isUsedInSpellcheck, $field->isUsedInSpellcheck());
    }

    /**
     * Data provider to test combinations of is_searchable/is_used_in_spellcheck for field configuration.
     *
     * @return array
     */
    public function getIsUsedInSpellcheckFieldConfigDataProvider()
    {
        return [
            [
                ['is_searchable' => true, 'is_used_in_spellcheck' => false],
                true,
                false,
            ],
            [
                ['is_searchable' => true, 'is_used_in_spellcheck' => true],
                true,
                true,
            ],
            [
                ['is_searchable' => false, 'is_used_in_spellcheck' => true],
                false,
                false,
            ],
        ];
    }

    /**
     * Test token count fields mapping generation.
     *
     * @return void
     */
    public function testTokenCountField()
    {
        $fieldType = FieldInterface::FIELD_TYPE_TOKEN_COUNT;

        $fieldConfig = [];
        $field = new Field('field', $fieldType, null, $fieldConfig);
        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_TOKEN_COUNT, $mappingPropertyConfig['type']);
        $this->assertEquals(FieldInterface::ANALYZER_STANDARD, $mappingPropertyConfig['analyzer']);
        $this->assertEquals(true, $mappingPropertyConfig['store']);
        $this->assertEquals(false, $mappingPropertyConfig['enable_position_increments']);

        $fieldConfig = ['default_search_analyzer' => FieldInterface::ANALYZER_EDGE_NGRAM];
        $field = new Field('field', $fieldType, null, $fieldConfig);
        $mappingPropertyConfig = $field->getMappingPropertyConfig();
        $this->assertEquals(FieldInterface::FIELD_TYPE_TOKEN_COUNT, $mappingPropertyConfig['type']);
        $this->assertEquals(FieldInterface::ANALYZER_EDGE_NGRAM, $mappingPropertyConfig['analyzer']);
    }

    /**
     * @dataProvider getMergeConfigFieldConfigDataProvider
     *
     * @param array  $fieldConfig        Field configuration from data provider
     * @param array  $config             Field configuration to merge, from data provider
     * @param bool   $isSearchable       Expected result for is_searchable property
     * @param bool   $isFilterable       Expected result for is_filterable property
     * @param bool   $isUsedForSortBy    Expected result for is_used_for_sort_by property
     * @param bool   $isUsedInSpellcheck Expected result for is_used_in_spellcheck property
     * @param int    $searchWeight       Expected result for search_weight property
     * @param string $defaultAnalyzer    Expected result for default_search_analyzer property
     */
    public function testMergeConfig(
        $fieldConfig,
        $config,
        $isSearchable,
        $isFilterable,
        $isUsedForSortBy,
        $isUsedInSpellcheck,
        $searchWeight,
        $defaultAnalyzer
    ) {
        $fieldType = FieldInterface::FIELD_TYPE_TEXT;
        $field     = new Field('field', $fieldType, null, $fieldConfig);

        $field = $field->mergeConfig($config);

        $this->assertEquals($isSearchable, $field->isSearchable());
        $this->assertEquals($isFilterable, $field->isFilterable());
        $this->assertEquals($isUsedForSortBy, $field->isUsedForSortBy());
        $this->assertEquals($isUsedInSpellcheck, $field->isUsedInSpellcheck());
        $this->assertEquals($searchWeight, $field->getSearchWeight());
        $this->assertEquals($defaultAnalyzer, $field->getDefaultSearchAnalyzer());
    }

    /**
     * Data provider to test proper merging of existing field config with new config.

     * @return array
     */
    public function getMergeConfigFieldConfigDataProvider()
    {
        return [
            [
                [
                    'is_searchable'           => true,
                    'is_filterable'           => true,
                    'is_used_for_sort_by'     => false,
                    'is_used_in_spellcheck'   => false,
                    'search_weight'           => 1,
                    'default_search_analyzer' => 'standard',
                ],
                [
                    'is_searchable'           => true,
                    'is_filterable'           => true,
                    'is_used_for_sort_by'     => true,
                    'is_used_in_spellcheck'   => true,
                    'search_weight'           => 6,
                    'default_search_analyzer' => 'reference',
                ],
                true,
                true,
                true,
                true,
                6,
                'reference',
            ],
            [
                ['is_searchable' => true, 'is_used_in_spellcheck' => false],
                ['is_searchable' => true, 'is_used_in_spellcheck' => true, 'search_weight' => 6, 'default_search_analyzer' => 'reference' ],
                true,
                true,
                false,
                true,
                6,
                'reference',
            ],
            [
                ['is_searchable' => true, 'is_used_in_spellcheck' => false],
                ['is_searchable' => true, 'is_used_in_spellcheck' => true ],
                true,
                true,
                false,
                true,
                1,
                'standard',
            ],
            [
                ['is_searchable' => true, 'is_used_in_spellcheck' => false],
                [],
                true,
                true,
                false,
                false,
                1,
                'standard',
            ],
            [
                [],
                ['search_weight' => 6, 'default_search_analyzer' => 'reference'],
                false,
                true,
                false,
                false,
                6,
                'reference',
            ],
            [
                [],
                [],
                false,
                true,
                false,
                false,
                1,
                'standard',
            ],
        ];
    }
}
