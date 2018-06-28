<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
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
     * @throws \InvalidArgumentException When the default search type is invalid.
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
        $this->types              = $this->prepareTypes($types);
        $this->needInstall        = $needInstall;
        $this->defaultSearchType  = $defaultSearchType;

        if (!isset($this->types[$defaultSearchType])) {
            throw new \InvalidArgumentException("Default search $defaultSearchType does not exists in the index.");
        }
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
     * @throws \InvalidArgumentException When the type does not exists.
     *
     * {@inheritdoc}
     */
    public function getType($typeName)
    {
        if (!isset($this->types[$typeName])) {
            throw new \InvalidArgumentException("Type $typeName does not exists in the index.");
        }

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

    /**
     * Prepare the types added to the index (rekey the array).
     *
     * @param TypeInterface[] $types Installed types.
     *
     * @return TypeInterface[]
     */
    private function prepareTypes($types)
    {
        $preparedTypes = [];

        foreach ($types as $type) {
            $preparedTypes[$type->getName()] = $type;
        }

        return $preparedTypes;
    }
}
