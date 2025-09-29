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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\IndexInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Index\TypeInterface;

/**
 * Default implementation for ES indices (Smile\ElasticsuiteCore\Api\Index\IndexInterface).
 *
 * @category  Smile
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
     * @var MappingInterface
     */
    private $mapping;

    /**
     * Instantiate a new index.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @throws \InvalidArgumentException When the default search type is invalid.
     *
     * @param string           $identifier        Index real name.
     * @param string           $name              Index real name.
     * @param string           $defaultSearchType Default type used in searches.
     * @param MappingInterface $mapping           Index Mapping.
     * @param boolean          $needInstall       Indicates if the index needs to be installed.
     */
    public function __construct(
        $identifier,
        $name,
        $defaultSearchType,
        MappingInterface $mapping,
        $needInstall = false
    ) {
        $this->identifier         = $identifier;
        $this->name               = $name;
        $this->needInstall        = $needInstall;
        $this->defaultSearchType  = $defaultSearchType;
        $this->mapping            = $mapping;
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
    public function needInstall()
    {
        return $this->needInstall;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSearchType()
    {
        return $this->defaultSearchType;
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function useKnn(): bool
    {
        return $this->getMapping()->hasKnnFields();
    }
}
