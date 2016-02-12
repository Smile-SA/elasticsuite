<?php

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\TypeInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;

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

    private $datasources;

    /**
     *
     * @param string           $name    Type name
     * @param IndexInterface   $index   Type index
     * @param MappingInterface $mapping Type mappinng
     */
    public function __construct($name, MappingInterface $mapping, array $datasources)
    {
        $this->name    = $name;
        $this->mapping = $mapping;
        $this->datasources = $datasources;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\TypeInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\TypeInterface::getIndex()
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * (non-PHPdoc)
     * @see \Smile\ElasticSuiteCore\Api\Index\TypeInterface::getMapping()
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDatasources()
    {
        return $this->datasources;
    }


    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\DatasourceInterface[]
    */
    public function getDatasource($name)
    {
        return $this->datasources[$name];
    }
}