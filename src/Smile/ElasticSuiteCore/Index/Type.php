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

    /**
     *
     * @param string           $name    Type name
     * @param IndexInterface   $index   Type index
     * @param MappingInterface $mapping Type mappinng
     */
    public function __construct($name, MappingInterface $mapping)
    {
        $this->name    = $name;
        $this->mapping = $mapping;
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
}