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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Representation of a document type in an Elasticsearch index.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @deprecated since 2.8.0
 */
interface TypeInterface
{
    /**
     * Type name.
     *
     * @deprecated
     *
     * @return string
     */
    public function getName();

    /**
     * Mapping describing all the field of the current type.
     *
     * @deprecated
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\MappingInterface
     */
    public function getMapping();

    /**
     * Field use as unique id for the doc.
     *
     * @deprecated
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();
}
