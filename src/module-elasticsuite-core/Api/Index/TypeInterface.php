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
     * List of datasources used by this type.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDatasources();

    /**
     * Retrieve a datasource by name for the current type.
     *
     * @param string $name Datasource name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface
     */
    public function getDatasource($name);

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();
}
