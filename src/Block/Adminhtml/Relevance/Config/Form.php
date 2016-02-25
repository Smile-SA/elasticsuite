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
namespace Smile\ElasticSuiteCore\Block\Adminhtml\Relevance\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Factory;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;

/**
 * Relevance configuration edit form
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Form extends \Magento\Config\Block\System\Config\Form
{
    const SCOPE_CONTAINERS = "containers";
    const SCOPE_STORE_CONTAINERS = "containers_stores";

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
        //\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
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

       // $this->_scopeConfig = $scopeConfiguration;

        $this->_scopeLabels = [
            self::SCOPE_DEFAULT          => __('[GLOBAL]'),
            self::SCOPE_CONTAINERS       => __('[CONTAINER]'),
            self::SCOPE_STORE_CONTAINERS => __('[CONTAINER - STORE VIEW]'),
        ];
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

        $showInContainer = true; // $field->showInContainer();

        if ($showInStore == 1) {
            return $this->_scopeLabels[self::SCOPE_STORE_CONTAINERS];
        } elseif ($showInContainer == 1) {
            return $this->_scopeLabels[self::SCOPE_CONTAINERS];
        }

        return $this->_scopeLabels[self::SCOPE_DEFAULT];
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
        //$this->_logger->debug(get_class($this->_scopeConfig));
        //$this->_logger->debug("CALLING FOR {$path} {$this->getScope()} {$this->getScopeCode()} ");
        return $this->_scopeConfig->getValue($path, $this->getScope(), $this->getScopeCode());
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
            $scope = self::SCOPE_DEFAULT;

            if ($this->getContainerCode()) {
                $scope = self::SCOPE_CONTAINERS;
            }
            if ($this->getStoreCode()) {
                $scope = self::SCOPE_STORE_CONTAINERS;
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
     * @return $this
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _initObjects()
    {
        // @codingStandardsIgnoreEnd

        $this->_configDataObject = $this->_configFactory->create(
            [
                'data' => [
                    'section'   => $this->getSectionCode(),
                    'container' => $this->getContainerCode(),
                    'store'     => $this->getStoreCode(),
                ],
            ]
        );

        $this->_configData = $this->_configDataObject->load();
        $this->_fieldsetRenderer = $this->_fieldsetFactory->create();
        $this->_fieldRenderer = $this->_fieldFactory->create();

        $this->_logger->debug("COUCOU");
        $this->_logger->debug(get_class($this->_configDataObject));
        $this->_logger->debug(get_class($this->_configFactory));
        $this->_logger->debug(print_r($this->_configData, true));

        return $this;
    }
}
