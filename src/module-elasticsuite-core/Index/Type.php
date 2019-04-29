<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\TypeInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;

/**
 * Default implementation for ES document types (Smile\ElasticsuiteCore\Api\Index\TypeInterface).
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @deprecated since 2.8.0
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
     * @var \Smile\ElasticsuiteCore\Api\Index\MappingInterface
     */
    private $mapping;

    /**
     * Type constructor.
     *
     * @param string           $name    Name of the type.
     * @param MappingInterface $mapping Mapping of the type.
     */
    public function __construct($name, MappingInterface $mapping)
    {
        $this->name        = $name;
        $this->mapping     = $mapping;
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
    * {@inheritDoc}
    */
    public function getIdField()
    {
        return $this->getMapping()->getIdField();
    }
}
