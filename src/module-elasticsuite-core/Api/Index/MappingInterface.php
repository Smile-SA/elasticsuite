<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Representation of a Elasticsearch type mapping.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface MappingInterface
{
    const DEFAULT_SEARCH_FIELD       = 'search';
    const DEFAULT_SPELLING_FIELD     = 'spelling';
    const DEFAULT_AUTOCOMPLETE_FIELD = 'autocomplete';

    /**
     * List of the properties of the mapping.
     *
     * @return array
     */
    public function getProperties();

    /**
     * List of the fields used to build the mapping.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    public function getFields();

    /**
     * Return a field of the mapping by name.
     *
     * @param string $name Field name
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getField($name);

    /**
     * Return the mapping as an array you can put into ES through the mapping API.
     *
     * @return array
     */
    public function asArray();

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();
}
