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

namespace Smile\ElasticsuiteCore\Search\Request\Query\Fulltext;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Indicates if a field is used in fuzzy search.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FuzzyFieldFilter extends SearchableFieldFilter
{
    /**
     * {@inheritDoc}
     */
    public function filterField(FieldInterface $field)
    {
        return parent::filterField($field) && $field->isUsedInSpellcheck();
    }
}
