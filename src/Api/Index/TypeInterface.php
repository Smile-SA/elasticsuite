<?php
/**
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Api\Index;

interface TypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\MappingInterface
     */
    public function getMapping();

    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDatasources();

    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDatasource($name);
}
