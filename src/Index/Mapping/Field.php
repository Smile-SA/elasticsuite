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
namespace Smile\ElasticSuiteCore\Index\Mapping;

use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;

class Field implements FieldInterface
{
    private $name;
    private $type;
    private $isSearchable;
    private $isFilterable;
    private $isFilterableInSearch;
    private $isUsedInSpellcheck;
    private $isUsedInAutocomplete;
    private $searchWeight;
    private $nestedPath;

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

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isSearchable()
    {
        return $this->isSearchable;
    }

    public function isFilterable()
    {
        return $this->isFilterable;
    }

    public function isFilterableInSearch()
    {
        return $this->isFilterableInSearch;
    }

    public function isUsedInSpellcheck()
    {
        return $this->isUsedInSpellcheck;
    }

    public function isUsedInAutocomplete()
    {
        return $this->isUsedInAutocomplete;
    }

    public function getSearchWeight()
    {
        return $this->getSearchWeight();
    }

    public function isNested()
    {
        return is_string($this->nestedPath) && !empty($this->nestedPath);
    }

    public function getNestedPath()
    {
        return $this->nestedPath;
    }
}
