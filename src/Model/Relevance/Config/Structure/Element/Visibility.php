<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element;

use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * Relevance Config field visibility
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
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
        $this->storeManager  = $storeManager;
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
            !$this->moduleManager->isOutputEnabled($element->getAttribute('if_module_enabled'))) {
            return false;
        }

        $showInScope = [
            RequestContainerInterface::SCOPE_TYPE_DEFAULT => $element->getAttribute('showInDefault'),
            RequestContainerInterface::SCOPE_CONTAINERS => $element->getAttribute('showInContainer'),
            RequestContainerInterface::SCOPE_STORE_CONTAINERS => $element->getAttribute('showInStore'),
        ];

        if ($this->storeManager->isSingleStoreMode()) {
            $result = !$element->getAttribute('hide_in_single_store_mode') && array_sum($showInScope);

            return $result;
        }

        return !empty($showInScope[$scope]);
    }
}
