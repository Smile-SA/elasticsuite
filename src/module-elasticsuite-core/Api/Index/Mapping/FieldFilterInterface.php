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

namespace Smile\ElasticsuiteCore\Api\Index\Mapping;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * An interface that allowed to specify a field filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface FieldFilterInterface
{
    /**
     * Indicates if the field has to be added to the list or not.
     *
     * @param FieldInterface $field Field to be tested.
     *
     * @return boolean
     */
    public function filterField(FieldInterface $field);
}
