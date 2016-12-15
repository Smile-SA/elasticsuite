<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Factory;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticsuiteCore\Search\Request\RelevanceConfig\App\Config as RelevanceConfig;

/**
 * Relevance configuration edit form
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Form extends \Magento\Config\Block\System\Config\Form
{
    /**
     * @var RelevanceConfig
     */
    private $relevanceConfig;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context                   $context         Application Context
     * @param \Magento\Framework\Registry                               $registry        Magento Registry
     * @param FormFactory                                               $formFactory     Form Factory
     * @param Factory                                                   $configFactory   Configuration Factory
     * @param Structure                                                 $configStructure Configuration Structure
     * @param \Magento\Config\Block\System\Config\Form\Fieldset\Factory $fieldsetFactory Fieldset Factory
     * @param \Magento\Config\Block\System\Config\Form\Field\Factory    $fieldFactory    Field Factory
     * @param RelevanceConfig                                           $relevanceConfig Relevance Configuration
     * @param array                                                     $data            Object Data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Factory $configFactory,
        Structure $configStructure,
        \Magento\Config\Block\System\Config\Form\Fieldset\Factory $fieldsetFactory,
        \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory,
        RelevanceConfig $relevanceConfig,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $configFactory,
            $configStructure,
            $fieldsetFactory,
            $fieldFactory,
            $data
        );

        $this->relevanceConfig = $relevanceConfig;
        $this->_scopeLabels = [
            ContainerScopeInterface::SCOPE_DEFAULT          => __('[GLOBAL]'),
            ContainerScopeInterface::SCOPE_CONTAINERS       => __('[CONTAINER]'),
            ContainerScopeInterface::SCOPE_STORE_CONTAINERS => __('[CONTAINER - STORE VIEW]'),
        ];
    }

    /**
     * Check if can use default value
     *
     * @param int $fieldValue The field value
     *
     * @return boolean
     */
    public function canUseDefaultValue($fieldValue)
    {
        if ($this->getScope() == ContainerScopeInterface::SCOPE_STORE_CONTAINERS && $fieldValue) {
            return true;
        }

        if ($this->getScope() == ContainerScopeInterface::SCOPE_CONTAINERS && $fieldValue) {
            return true;
        }

        return false;
    }

    /**
     * Check if can use website value
     *
     * @param int $fieldValue The field value
     *
     * @return boolean
     */
    public function canUseContainerValue($fieldValue)
    {
        if ($this->getScope() == ContainerScopeInterface::SCOPE_STORE_CONTAINERS && $fieldValue) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve label for scope
     *
     * @param Field $field The field
     *
     * @return string
     */
    public function getScopeLabel(Field $field)
    {
        $showInStore = $field->showInStore();

        $showInContainer = $field->showInContainer();

        if ($showInStore == 1) {
            return $this->_scopeLabels[ContainerScopeInterface::SCOPE_STORE_CONTAINERS];
        } elseif ($showInContainer == 1) {
            return $this->_scopeLabels[ContainerScopeInterface::SCOPE_CONTAINERS];
        }

        return $this->_scopeLabels[ContainerScopeInterface::SCOPE_DEFAULT];
    }

    /**
     * Retrieve container code
     *
     * @return string
     */
    public function getContainerCode()
    {
        return $this->getRequest()->getParam('container', '');
    }

    /**
     * Get config value
     *
     * @param string $path The config value path
     *
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->relevanceConfig->getValue($path, $this->getScope(), $this->getScopeCode());
    }

    /**
     * Retrieve current scope
     *
     * @return string
     */
    public function getScope()
    {
        $scope = $this->getData('scope');

        if ($scope === null) {
            $scope = ContainerScopeInterface::SCOPE_DEFAULT;

            if ($this->getContainerCode()) {
                $scope = ContainerScopeInterface::SCOPE_CONTAINERS;
            }
            if ($this->getStoreCode()) {
                $scope = ContainerScopeInterface::SCOPE_STORE_CONTAINERS;
            }

            $this->setScope($scope);
        }

        return $scope;
    }

    /**
     * Get current scope code
     *
     * @return string
     */
    public function getScopeCode()
    {
        $scopeCode = $this->getData('scope_code');

        if ($scopeCode === null) {
            $scopeCode = 'default';
            if ($this->getStoreCode()) {
                $store = $this->_storeManager->getStore($this->getStoreCode());
                $scopeCode = $store->getId();
                if ($this->getContainerCode() && ($this->getContainerCode() != "")) {
                    $scopeCode = $this->getContainerCode() . "|" . $scopeCode;
                }
            } elseif ($this->getContainerCode()) {
                $scopeCode = $this->getContainerCode();
            }

            $this->setScopeCode($scopeCode);
        }

        return $scopeCode;
    }

    /**
     * Initialize objects required to render config form
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return $this
     */
    protected function _initObjects()
    {
        $this->_configDataObject = $this->_configFactory->create(
            [
                'data' => [
                    'section'   => $this->getSectionCode(),
                    'container' => $this->getContainerCode(),
                    'store'     => $this->getStoreCode(),
                ],
            ]
        );

        $this->_configData       = $this->_configDataObject->load();
        $this->_fieldsetRenderer = $this->_fieldsetFactory->create();
        $this->_fieldRenderer    = $this->_fieldFactory->create();

        return $this;
    }

    /**
     * Init form element from config.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field       Form field.
     * @param \Magento\Framework\Data\Form\Element\Fieldset        $fieldset    Form fieldset.
     * @param string                                               $path        Config path.
     * @param string                                               $fieldPrefix Field name prefix.
     * @param string                                               $labelPrefix Field label prefix.
     */
    protected function _initElement(
        \Magento\Config\Model\Config\Structure\Element\Field $field,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        $path,
        $fieldPrefix = '',
        $labelPrefix = ''
    ) {
        $inherit = true;

        $data = $this->getConfigValue($path);
        if (array_key_exists($path, $this->_configData)) {
            $data = $this->_configData[$path];
            $inherit = false;
        } elseif ($field->getConfigPath() !== null) {
            $data = $this->getConfigValue($field->getConfigPath());
        }

        $fieldRendererClass = $field->getFrontendModel();
        $fieldRenderer      = $this->_fieldRenderer;
        if ($fieldRendererClass) {
            $fieldRenderer = $this->_layout->getBlockSingleton($fieldRendererClass);
        }

        $fieldRenderer->setForm($this);
        $fieldRenderer->setConfigData($this->_configData);

        $elementName = $this->_generateElementName($field->getPath(), $fieldPrefix);
        $elementId = $this->_generateElementId($field->getPath($fieldPrefix));

        if ($field->hasBackendModel()) {
            $backendModel = $field->getBackendModel();
            $backendModel->setPath(
                $path
            )->setValue(
                $data
            )->setContainer(
                $this->getContainerCode()
            )->setStore(
                $this->getStoreCode()
            )->afterLoad();
            $data = $backendModel->getValue();
        }

        $dependencies = $field->getDependencies($fieldPrefix, $this->getStoreCode());
        $this->_populateDependenciesBlock($dependencies, $elementId, $elementName);

        $sharedClass = $this->_getSharedCssClass($field);
        $requiresClass = $this->_getRequiresCssClass($field, $fieldPrefix);

        $formField = $fieldset->addField(
            $elementId,
            $field->getType(),
            [
                'name' => $elementName,
                'label' => $field->getLabel($labelPrefix),
                'comment' => $field->getComment($data),
                'tooltip' => $field->getTooltip(),
                'hint' => $field->getHint(),
                'value' => $data,
                'inherit' => $inherit,
                'class' => $field->getFrontendClass() . $sharedClass . $requiresClass,
                'field_config' => $field->getData(),
                'scope' => $this->getScope(),
                'scope_id' => $this->getScopeId(),
                'scope_label' => $this->getScopeLabel($field),
                'can_use_default_value' => $this->canUseDefaultValue($field->showInDefault()),
                'can_use_container_value' => $this->canUseContainerValue($field->showInContainer()),
            ]
        );

        $field->populateInput($formField);

        if ($field->hasValidation()) {
            $formField->addClass($field->getValidation());
        }
        if ($field->getType() == 'multiselect') {
            $formField->setCanBeEmpty($field->canBeEmpty());
        }
        if ($field->hasOptions()) {
            $formField->setValues($field->getOptions());
        }
        $formField->setRenderer($fieldRenderer);
    }
}
