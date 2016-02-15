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
namespace Smile\ElasticSuiteCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Mapping extends AbstractHelper
{
    const OPTION_TEXT_PREFIX = 'option_text';

    /**
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getOptionTextFieldName($fieldName)
    {
        return sprintf("%s_%s", self::OPTION_TEXT_PREFIX, $fieldName);
    }
}
