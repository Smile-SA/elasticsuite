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

use Magento\Config\Model\Config\Structure\Element\Field;

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
            return $this->_scopeLabels[self::SCOPE_STORES];
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
     * Initialize objects required to render config form
     *
     * @return $this
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function _initObjects()
    {
        // @codingStandardsIgnoreEnd
        $this->_scopeLabels = [
            self::SCOPE_DEFAULT    => __('[GLOBAL]'),
            self::SCOPE_CONTAINERS => __('[CONTAINER]'),
            self::SCOPE_STORES     => __('[STORE VIEW]'),
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
        $this->_logger->debug(get_class($this->_configDataObject));
        $this->_logger->debug(get_class($this->_configFactory));
        $this->_logger->debug(print_r($this->_configData, true));

        return $this;
    }
}
