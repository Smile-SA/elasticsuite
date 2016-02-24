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
namespace Smile\ElasticSuiteCore\Block\Adminhtml\Relevance\Config;

/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Form extends \Magento\Config\Block\System\Config\Form
{
    const SCOPE_CONTAINERS = "containers";

    /**
     * Retrieve label for scope
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @return string
     */
    public function getScopeLabel(\Magento\Config\Model\Config\Structure\Element\Field $field)
    {
        $showInStore = $field->showInStore();

        $showInContainer = true; //$field->showInContainer();

        if ($showInStore == 1) {
            return $this->_scopeLabels[self::SCOPE_STORES];
        } elseif ($showInContainer == 1) {
            return $this->_scopeLabels[self::SCOPE_CONTAINERS];
        }

        return $this->_scopeLabels[self::SCOPE_DEFAULT];
    }

    /**
     * Initialize objects required to render config form
     *
     * @return $this
     */
    protected function _initObjects()
    {
        $this->_scopeLabels = [
            self::SCOPE_DEFAULT => __('[GLOBAL]'),
            self::SCOPE_CONTAINERS => __('[CONTAINER]'),
            self::SCOPE_STORES => __('[STORE VIEW]'),
        ];

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
        $this->_logger->debug(print_r($this->getRequest()->getParams(), true));
        $this->_logger->debug($this->getSectionCode());
        $this->_logger->debug($this->getContainerCode());
        $this->_logger->debug($this->getStoreCode());

        return $this;
    }

    public function getContainerCode()
    {
        return $this->getRequest()->getParam('container', '');
    }
}
