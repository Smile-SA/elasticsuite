<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\TypeInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;

/**
 * Default implementation for ES document types (Smile\ElasticSuiteCore\Api\Index\TypeInterface).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Type implements TypeInterface
{
    /**
     * Type name.
     *
     * @var string
     */
    private $name;

    /**
     * Type mapping.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\MappingInterface
     */
    private $mapping;

    /**
     * Type datasources.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
     */
    private $datasources;

    /**
     * Type construcor.
     *
     * @param string                                                  $name        Name of the type.
     * @param MappingInterface                                        $mapping     Mapping of the type.
     * @param \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[] $datasources Datasources of the type.
     */
    public function __construct($name, MappingInterface $mapping, array $datasources = [])
    {
        $this->name    = $name;
        $this->mapping = $mapping;
        $this->datasources = $datasources;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasources()
    {
        return $this->datasources;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource($name)
    {
        return $this->datasources[$name];
    }
}
