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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Section;

/**
 * Relevance Config composite field visibility
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Visibility extends \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Visibility
{
    /**
     * Check a configuration element visibility
     *
     * @param \Magento\Config\Model\Config\Structure\AbstractElement $element The config composite element
     * @param string                                                 $scope   The element scope
     *
     * @return bool
     */
    public function isVisible(\Magento\Config\Model\Config\Structure\AbstractElement $element, $scope)
    {
        if (!$element->isAllowed()) {
            return false;
        }

        $isVisible = parent::isVisible($element, $scope);

        if ($isVisible) {
            $isVisible = $element->hasChildren() || $element->getFrontendModel();
        }

        return $isVisible;
    }
}
