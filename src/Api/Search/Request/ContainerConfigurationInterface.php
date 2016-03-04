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

namespace Smile\ElasticSuiteCore\Api\Search\Request;

use Smile\ElasticSuiteCore\Search\Request\Config\BaseConfig;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;

/**
 * Search request container configuration interface.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface ContainerConfigurationInterface
{
    /**
     * Search request container name.
     *
     * @return string
     */
    public function getName();

    /**
     * Search request container index name.
     *
     * @return string
     */
    public function getIndexName();


    /**
     * Search request container document type name.
     *
     * @return string
     */
    public function getTypeName();

    /**
     * Search request container label.
     *
     * @return string
     */
    public function getLabel();


    /**
     * Search request container mapping.
     *
     * @return MappingInterface
     */
    public function getMapping();
}
