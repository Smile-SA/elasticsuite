<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Representation of a document type in an Elasticsearch index.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface TypeInterface
{
    /**
     * Type name.
     *
     * @return string
     */
    public function getName();

    /**
     * Mapping describing all the field of the current type.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\MappingInterface
     */
    public function getMapping();

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();
}
