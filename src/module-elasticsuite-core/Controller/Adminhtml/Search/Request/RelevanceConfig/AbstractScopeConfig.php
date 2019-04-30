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

namespace Smile\ElasticsuiteCore\Controller\Adminhtml\Search\Request\RelevanceConfig;

use Magento\Backend\App\Action\Context;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Config\Model\Config;
use Magento\Config\Model\Config\Structure;

/**
 * Abstract scoped configuration edition container
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
abstract class AbstractScopeConfig extends AbstractConfig
{
    /**
     * @var Config
     */
    protected $backendConfig;

    /**
     * Class constructor
     *
     * @param Context              $context         Action context
     * @param Structure            $configStructure Relevance configuration Structure
     * @param ConfigSectionChecker $sectionChecker  Configuration Section Checker
     * @param Config               $backendConfig   Configuration model
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        Config $backendConfig
    ) {
        $this->backendConfig = $backendConfig;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Sets scope for backend config
     *
     * @param string $sectionId The section being viewed/edited/saved
     *
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

        return $this->sectionChecker->isSectionAllowed($sectionId);
    }
}
