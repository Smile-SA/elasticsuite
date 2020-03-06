<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping\TypeEnforcer;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Boolean field type enforcer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class Boolean extends AbstractTypeEnforcer
{
    /**
     * {@inheritDoc}
     */
    protected function collectFields()
    {
        foreach ($this->mapping->getFields() as $field) {
            if ($field->getType() === FieldInterface::FIELD_TYPE_BOOLEAN) {
                $this->fields[] = $field;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function enforceField(&$fieldValue)
    {
        $fieldValue = (bool) $fieldValue;
    }
}
