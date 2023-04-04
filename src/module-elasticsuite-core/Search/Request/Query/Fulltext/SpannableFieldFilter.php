<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query\Fulltext;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;

/**
 * Indicates if a field can be used for span queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SpannableFieldFilter implements FieldFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function filterField(FieldInterface $field)
    {
        return $field->getType() == FieldInterface::FIELD_TYPE_TEXT
            && $field->isSearchable()
            && $field->isSpannable()
            && $field->isNested() === false;
    }
}
