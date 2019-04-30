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

/**
 * Class implementing this interface can provides field to mappings.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface DynamicFieldProviderInterface
{
    /**
     * Return a list of mapping fields.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    public function getFields();
}
