<?php

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