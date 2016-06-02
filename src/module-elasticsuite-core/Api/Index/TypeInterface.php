<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Api\Index;

/**
 * Representation of a document type in an ElasticSearch index.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteCore
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
     * @return \Smile\ElasticSuiteCore\Api\Index\MappingInterface
     */
    public function getMapping();

    /**
     * List of datasources used by this type.
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDatasources();

    /**
     * Retrieve a datasource by name for the current type.
     *
     * @param string $name Datasource name.
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface
     */
    public function getDatasource($name);

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();
}
