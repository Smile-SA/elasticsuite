<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Default implementation for ES indices (Smile\ElasticsuiteCore\Api\Index\IndexInterface).
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
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
     * @var \Smile\ElasticsuiteCore\Api\Index\TypeInterface[]
     */
    private $types;

    /**
     * Indicates if index is installed.
     *
     * @var boolean
     */
    private $needInstall;

    /**
     * @var string
     */
    private $defaultSearchType;

    /**
     * Instanciate a new index.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param string          $identifier        Index real name.
     * @param string          $name              Index real name.
     * @param TypeInterface[] $types             Index current types.
     * @param string          $defaultSearchType Default type used in searches.
     * @param boolean         $needInstall       Indicates if the index needs to be installed.
     */
    public function __construct($identifier, $name, array $types, $defaultSearchType, $needInstall = false)
    {
        $this->identifier         = $identifier;
        $this->name               = $name;
        $this->types              = $types;
        $this->needInstall        = $needInstall;
        $this->defaultSearchType  = $defaultSearchType;
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

    /**
     * {@inheritDoc}
     */
    public function getDefaultSearchType()
    {
        return $this->getType($this->defaultSearchType);
    }
}
