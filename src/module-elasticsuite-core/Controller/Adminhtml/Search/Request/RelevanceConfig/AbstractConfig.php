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

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\RequestInterface;

/**
 * Abstract configuration controller
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
abstract class AbstractConfig extends AbstractAction
{
    /**
     * @var Structure
     */
    protected $configStructure;

    /**
     * @var ConfigSectionChecker
     */
    protected $sectionChecker;

    /**
     * Class constructor
     *
     * @param Context              $context         Action context
     * @param Structure            $configStructure Relevance configuration Structure
     * @param ConfigSectionChecker $sectionChecker  Configuration Section Checker
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker
    ) {
        parent::__construct($context);
        $this->configStructure = $configStructure;
        $this->sectionChecker = $sectionChecker;
    }

    /**
     * Check if current section is found and is allowed
     *
     * @param RequestInterface $request The current request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$request->getParam('section')) {
            $request->setParam('section', $this->configStructure->getFirstSection()->getId());
        }

        return parent::dispatch($request);
    }

    /**
     * Check is allow modify system configuration
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $sectionId = $this->_request->getParam('section');

        return $this->configStructure->getElement($sectionId)->isAllowed()
        ||  $this->_authorization->isAllowed('Smile_ElasticsuiteCore::manage_relevance');
    }
}
