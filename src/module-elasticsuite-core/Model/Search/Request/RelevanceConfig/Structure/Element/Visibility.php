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
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element;

use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;

/**
 * Relevance Config field visibility
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Visibility
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * Visibility constructor.
     *
     * @param \Magento\Framework\Module\Manager          $moduleManager The module Manager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager  The store manager
     */
    public function __construct(Manager $moduleManager, StoreManagerInterface $storeManager)
    {
        $this->moduleManager = $moduleManager;
        $this->storeManager = $storeManager;
    }

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
        if ($element->getAttribute('if_module_enabled') &&
            !$this->moduleManager->isOutputEnabled($element->getAttribute('if_module_enabled'))
        ) {
            return false;
        }

        $showInScope = [
            ContainerScopeInterface::SCOPE_DEFAULT          => $element->getAttribute('showInDefault'),
            ContainerScopeInterface::SCOPE_CONTAINERS       => $element->getAttribute('showInContainer'),
            ContainerScopeInterface::SCOPE_STORE_CONTAINERS => $element->getAttribute('showInStore'),
        ];

        if ($this->storeManager->isSingleStoreMode()) {
            $result = !$element->getAttribute('hide_in_single_store_mode') && array_sum($showInScope);

            return $result;
        }

        return !empty($showInScope[$scope]);
    }
}
