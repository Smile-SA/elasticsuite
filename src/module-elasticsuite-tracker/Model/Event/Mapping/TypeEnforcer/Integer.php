<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping\TypeEnforcer;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;

/**
 * Integer field type enforcer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class Integer extends AbstractTypeEnforcer
{
    /** @var string */
    const PURE_INTEGER_PATTERN = '#^[0-9]+$#';

    /**
     * {@inheritDoc}
     */
    protected function collectFields()
    {
        foreach ($this->mapping->getFields() as $field) {
            if ($field->getType() === FieldInterface::FIELD_TYPE_INTEGER) {
                $this->fields[] = $field;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function enforceField(&$fieldValue)
    {
        $fieldValue = (int) $this->decodeIfNeeded($fieldValue);
    }

    /**
     * Checks if the provided value looks like a base64 encoded integer and if so, returns it decoded.
     * Otherwise, returns the value as is.
     *
     * @param mixed $content Content to decode if needed.
     *
     * @return mixed
     */
    protected function decodeIfNeeded($content)
    {
        if (0 === (int) trim($content)) {
            $decodedContent = base64_decode(trim($content), true);
            if ((false !== $decodedContent) && preg_match(self::PURE_INTEGER_PATTERN, $decodedContent)) {
                $content = $decodedContent;
            }
        }

        return $content;
    }
}
