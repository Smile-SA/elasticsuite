<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Controller\Adminhtml\Search\Request\RelevanceConfig;

use Magento\Backend\App\Action\Context;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

/**
 * Save current state of open tabs controller (inherited logic from System\Config) dummy controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class State extends AbstractScopeConfig implements HttpPostActionInterface, HttpGetActionInterface
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * Constructor.
     *
     * @param Context              $context          Action context.
     * @param Structure            $configStructure  Relevance configuration Structure.
     * @param ConfigSectionChecker $sectionChecker   Configuration Section Checker.
     * @param RelevanceConfig      $backendConfig    Configuration model.
     * @param RawFactory           $resultRawFactory Raw result factory.
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        RelevanceConfig $backendConfig,
        RawFactory $resultRawFactory
    ) {
        parent::__construct($context, $configStructure, $sectionChecker, $backendConfig);
        $this->resultRawFactory = $resultRawFactory;
    }

    /**
     * Pretend saving fieldset state through AJAX.
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();

        if ($this->getRequest()->getParam('isAjax')) {
            return $resultRaw->setContents('success');
        }

        return $resultRaw->setContents('failure');
    }

    /**
     * Check if access is allowed.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Smile_ElasticsuiteCore::manage_relevance');
    }
}
