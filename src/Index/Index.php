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

use Smile\ElasticSuiteCore\Api\Index\IndexInterface;
use Smile\ElasticSuiteCore\Api\Index\TypeInterface;

/**
 * Default implementation for ES indices (Smile\ElasticSuiteCore\Api\Index\IndexInterface).
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
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

    /**
     * Indicates if index is installed.
     *
     * @var boolean
     */
    private $needInstall;

    /**
     * Instanciate a new index.
     *
     * @param string                                            $identifier  Index real name.
     * @param string                                            $name        Index real name.
     * @param \Smile\ElasticSuiteCore\Api\Index\TypeInterface[] $types       Index current aliases.
     * @param boolean                                           $needInstall Indicates if the index needs
     *                                                                       to be installed.
     */
    public function __construct($identifier, $name, array $types, $needInstall = false)
    {
        $this->identifier  = $identifier;
        $this->name        = $name;
        $this->types       = $types;
        $this->needInstall = $needInstall;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
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
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($typeName)
    {
        return $this->types[$typeName];
    }

    /**
     * {@inheritdoc}
     */
    public function needInstall()
    {
        return $this->needInstall;
    }
}
