<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalogRule\Model\Rule\Condition;

/**
 * Product attribute search engine rule.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Magento\Rule\Model\Condition\Product\AbstractProduct
{
    /**
     * Set the target element name (name of the input into the form).
     *
     * @param string $elementName Target element name
     *
     * @return $this
     */
    public function setElementName($elementName)
    {
        $this->elementName = $elementName;

        return $this;
    }
}
