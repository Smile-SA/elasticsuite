<?php

namespace Smile\ElasticSuiteCore\Api\Index;

interface IndexInterface
{
    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return \Smile\ElasticSuiteCore\Api\Index\TypeInterface[]
     */
    public function getTypes();

    /**
     *
     * @param strinh $typeName
     *
     * @return \Smile\ElasticSuiteCore\Api\Index\TypeInterface
     */
    public function getType($typeName);


    /**
     * @return bool
     */
    public function needInstall();

}