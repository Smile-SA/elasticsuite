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
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Cache\FrontendInterface;
use Magento\Framework\Stdlib\StringUtils;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Factory;

/**
 * Save action for relevance configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Save extends AbstractConfig
{
    /**
     * Backend Config Model Factory
     *
     * @var \Magento\Config\Model\Config\Factory
     */
    protected $configFactory;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var StringUtils
     */
    protected $string;

    /**
     * Class constructor
     *
     * @param Context              $context         Action context
     * @param Structure            $configStructure Relevance configuration Structure
     * @param ConfigSectionChecker $sectionChecker  Configuration Section Checker
     * @param Factory              $configFactory   Configuration Factory
     * @param FrontendInterface    $cache           Cache instance
     * @param StringUtils          $string          String helper
     */
    public function __construct(
        Context $context,
        Structure $configStructure,
        ConfigSectionChecker $sectionChecker,
        Factory $configFactory,
        FrontendInterface $cache,
        StringUtils $string
    ) {
        parent::__construct($context, $configStructure, $sectionChecker);
        $this->configFactory = $configFactory;
        $this->cache = $cache;
        $this->string = $string;
    }

    /**
     * Save configuration
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        try {
            $section = $this->getRequest()->getParam('section');
            $container = $this->getRequest()->getParam('container');
            $store = $this->getRequest()->getParam('store');

            $configData = [
                'section' => $section,
                'container' => $container,
                'store' => $store,
                'groups' => $this->getGroupsForSave(),
            ];

            /** @var \Magento\Config\Model\Config $configModel  */
            $configModel = $this->configFactory->create(['data' => $configData]);
            $configModel->save();

            $this->messageManager->addSuccess(__('You saved the configuration.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $messages = explode("\n", $e->getMessage());
            foreach ($messages as $message) {
                $this->messageManager->addError($message);
            }
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong while saving this configuration:') . ' ' . $e->getMessage()
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath(
            '*/*/edit',
            [
                '_current' => ['section', 'container', 'store'],
                '_nosid' => true,
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * Get groups for save
     *
     * @return array|null
     */
    protected function getGroupsForSave()
    {
        $groups = $this->getRequest()->getPost('groups');
        $files = $this->getRequest()->getFiles('groups');

        if ($files && is_array($files)) {
            /**
             * Carefully merge $_FILES and $_POST information
             * None of '+=' or 'array_merge_recursive' can do this correct
             */
            foreach ($files as $groupName => $group) {
                $data = $this->processNestedGroups($group);
                if (!empty($data)) {
                    if (!empty($groups[$groupName])) {
                        $groups[$groupName] = array_merge_recursive((array) $groups[$groupName], $data);
                    } else {
                        $groups[$groupName] = $data;
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * Process nested groups
     *
     * @param mixed $group The configuration groups
     *
     * @return array
     */
    protected function processNestedGroups($group)
    {
        $data = [];

        if (isset($group['fields']) && is_array($group['fields'])) {
            foreach ($group['fields'] as $fieldName => $field) {
                if (!empty($field['value'])) {
                    $data['fields'][$fieldName] = ['value' => $field['value']];
                }
            }
        }

        if (isset($group['groups']) && is_array($group['groups'])) {
            foreach ($group['groups'] as $groupName => $groupData) {
                $nestedGroup = $this->processNestedGroups($groupData);
                if (!empty($nestedGroup)) {
                    $data['groups'][$groupName] = $nestedGroup;
                }
            }
        }

        return $data;
    }
}
