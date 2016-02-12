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
namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Api\Index\TypeInterface;

class Index implements IndexInterface
{
    /**
     * Index identifier.
     *
     * @var string
     */
    private $identifier;

    /**
     * Name of the index.
     *
     * @var string
     */
    private $name;

    /**
     * Index types.
     *
     * @var \Smile\ElasticSuiteCore\Api\Index\TypeInterface[]
     */
    private $types;

    private $needInstall;

    /**
     *
     * @param string $identifier Index real name.
     * @param string $name       Index real name.
     * @param array  $aliases    Index current aliases.
     */
    public function __construct($identifier, $name, array $types, $needInstall = false)
    {
        $this->identifier = $identifier;
        $this->name  = $name;
        $this->types = $types;
        $this->needInstall = $needInstall;
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexInterface::getIdentifier()
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     * @see \Smile\ElasticSuiteCore\Api\Index\IndexInterface::getTypes()
     */
    public function getTypes()
    {
        return $this->types;
    }

    public function getType($typeName)
    {
        return $this->types[$typeName];
    }

    public function needInstall()
    {
        return $this->needInstall;
    }
}