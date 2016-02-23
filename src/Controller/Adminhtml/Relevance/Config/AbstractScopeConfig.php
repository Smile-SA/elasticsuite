<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Controller\Adminhtml\Relevance\Config;


/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Smile\ElasticSuiteCore\Controller\Adminhtml\Relevance;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

abstract class AbstractScopeConfig extends \Smile\ElasticSuiteCore\Controller\Adminhtml\Relevance\AbstractConfig
{
    /**
     * @var \Magento\Config\Model\Config
     */
    protected $backendConfig;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param ConfigSectionChecker $sectionChecker
     * @param \Magento\Config\Model\Config $backendConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        RequestContainerInterface $requestConfig,
        \Magento\Config\Model\Config $backendConfig
    ) {
        $this->backendConfig = $backendConfig;
        parent::__construct($context, $configStructure, $sectionChecker, $requestConfig);
    }

    /**
     * Sets scope for backend config
     *
     * @param string $sectionId
     * @return bool
     */
    protected function isSectionAllowed($sectionId)
    {
        $container = $this->getRequest()->getParam('container');
        $store = $this->getRequest()->getParam('store');
        if ($store) {
            $this->backendConfig->setStore($store);
        } elseif ($container) {
            $this->backendConfig->setContainer($container);
        }

        return parent::isSectionAllowed($sectionId);
    }
}
