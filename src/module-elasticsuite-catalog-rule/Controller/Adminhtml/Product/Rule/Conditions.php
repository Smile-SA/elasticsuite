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

namespace Smile\ElasticSuiteCatalogRule\Controller\Adminhtml\Product\Rule;

use Magento\Backend\App\Action;
use Magento\Rule\Model\Condition\AbstractCondition;

/**
 * Catalog search rule contribution controller action used to generate new children rules.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Conditions extends Action
{
    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var arrray
     */
    private $acls = [];

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context              $context     Context.
     * @param \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory Search engine rule factory.
     * @param array                                            $acls        List of resource allowed to use the controller.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Smile\ElasticSuiteCatalogRule\Model\RuleFactory $ruleFactory,
        $acls = []
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->acls        = $acls;
        parent::__construct($context);
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $conditionId = $this->getRequest()->getParam('id');
        $typeData = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $className = $typeData[0];

        $rule = $this->ruleFactory->create();

        $model = $this->_objectManager->create($className)
            ->setId($conditionId)
            ->setType($className)
            ->setRule($rule)
            ->setPrefix('conditions');

        $model->setElementName($this->getRequest()->getParam('element_name'));

        if (!empty($typeData[1])) {
            $model->setAttribute($typeData[1]);
        }

        $result = '';
        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $result = $model->asHtmlRecursive();
        }
        $this->getResponse()->setBody($result);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _isAllowed()
    {
        $isAllowed = false;

        foreach ($this->acls as $acl) {
            $isAllowed = $isAllowed || $this->_authorization->isAllowed($acl);
        }

        return $isAllowed;
    }
}
