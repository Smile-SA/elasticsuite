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

namespace Smile\ElasticSuiteCore\Index\Mapping;

use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Default implementation for ES mapping field (Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Field implements FieldInterface
{
    /**
     * @var boolean
     */
    private $name;

    /**
     * @var boolean
     */
    private $type;

    /**
     * @var boolean
     */
    private $isSearchable;

    /**
     * @var boolean
     */
    private $isFilterable;

    /**
     * @var boolean
     */
    private $isFilterableInSearch;

    /**
     * @var boolean
     */
    private $isUsedInSpellcheck;

    /**
     * @var boolean
     */
    private $isUsedInAutocomplete;

    /**
     * @var boolean
     */
    private $searchWeight;

    /**
     * @var boolean
     */
    private $nestedPath;


    /**
     * Instanciate a new field.
     *
     * @param string  $name                 Field name.
     * @param string  $type                 Field type.
     * @param boolean $isSearchable         Is the field searchable.
     * @param boolean $isFilterable         Is the field filterabe in navigation.
     * @param boolean $isFilterableInSearch Is the field filterabe in search.
     * @param boolean $isUsedInSpellcheck   Is the field used by the spellchecker.
     * @param boolean $isUsedInAutocomplete Is the field used in autocomplete.
     * @param integer $searchWeight         Field weight in search operation.
     * @param string  $nestedPath           If the field is nested, the nested path have to be provided here.
     */
    public function __construct(
        $name,
        $type = 'string',
        $isSearchable = true,
        $isFilterable = false,
        $isFilterableInSearch = false,
        $isUsedInSpellcheck = false,
        $isUsedInAutocomplete = false,
        $searchWeight = 1,
        $nestedPath = false
    ) {
        $this->name = (string) $name;
        $this->type = (string) $type;
        $this->isSearchable = (bool) $isSearchable;
        $this->isFilterable = (bool) $isFilterable;
        $this->isFilterableInSearch = (bool) $isFilterableInSearch;
        $this->isUsedInSpellcheck = (bool) $isUsedInSpellcheck;
        $this->isUsedInAutocomplete = (bool) $isUsedInAutocomplete;
        $this->searchWeight = (int) $searchWeight;
        $this->nestedPath = $nestedPath;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return $this->isSearchable;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable()
    {
        return $this->isFilterable;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterableInSearch()
    {
        return $this->isFilterableInSearch;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInSpellcheck()
    {
        return $this->isUsedInSpellcheck;
    }

    /**
     * {@inheritdoc}
     */
    public function isUsedInAutocomplete()
    {
        return $this->isUsedInAutocomplete;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchWeight()
    {
        return $this->getSearchWeight();
    }

    /**
     * {@inheritdoc}
     */
    public function isNested()
    {
        return is_string($this->nestedPath) && !empty($this->nestedPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getNestedPath()
    {
        return $this->nestedPath;
    }
}
