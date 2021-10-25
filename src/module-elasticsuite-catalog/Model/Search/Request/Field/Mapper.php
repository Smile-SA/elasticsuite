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

namespace Smile\ElasticsuiteCatalog\Model\Search\Request\Field;

/**
 * Search Request Field Mapping
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Mapper
{
    /**
     * @var string[]
     */
    private $fieldNameMapping = [
        'category_ids' => 'category.category_id',
        'category_id' => 'category.category_id',
        'category_uid' => 'category.category_id',
        'position' => 'category.position',
        'price' => 'price.price',
    ];

    /**
     * Mapper constructor.
     *
     * @param array $fieldNameMapping Search request field mapping
     */
    public function __construct(
        array $fieldNameMapping = []
    ) {
        $this->fieldNameMapping = \array_merge($this->fieldNameMapping, $fieldNameMapping);
    }

    /**
     * Returns name of the field in the search engine mapping.
     *
     * @param string $fieldName Request field name.
     *
     * @return string
     */
    public function getMappedFieldName(string $fieldName)
    {
        if (isset($this->fieldNameMapping[$fieldName])) {
            $fieldName = $this->fieldNameMapping[$fieldName];
        }

        return $fieldName;
    }

    /**
     * Returns field name mappings
     *
     * @return string[]
     */
    public function getFieldNameMappings()
    {
        return $this->fieldNameMapping;
    }
}
