<?php

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
}