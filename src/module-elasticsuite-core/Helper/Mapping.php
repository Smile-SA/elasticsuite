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

namespace Smile\ElasticsuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Mapping related usefull methods.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Mapping extends AbstractHelper
{
    /**
     * @var string
     */
    const OPTION_TEXT_PREFIX = 'option_text';

    /**
     * Transform a field name into it's option value field form.
     *
     * @param string $fieldName The field name to be converted.
     *
     * @return string
     */
    public function getOptionTextFieldName($fieldName)
    {
        return sprintf("%s_%s", self::OPTION_TEXT_PREFIX, $fieldName);
    }
}
