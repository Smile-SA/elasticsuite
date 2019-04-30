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
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Config\Model\Config\Structure;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

/**
 * Index action for relevance configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Index extends AbstractScopeConfig
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Class constructor
     *
     * @param Context              $context              Action context
     * @param Structure            $configStructure      Relevance configuration Structure
     * @param ConfigSectionChecker $sectionChecker       Configuration Section Checker
     * @param RelevanceConfig      $backendConfig        Configuration model
     * @param ForwardFactory       $resultForwardFactory Magento Forward Factory
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        RelevanceConfig $backendConfig,
        ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context, $configStructure, $sectionChecker, $backendConfig);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();

        return $resultForward->forward('edit');
    }
}
