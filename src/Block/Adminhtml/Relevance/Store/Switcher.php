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

namespace Smile\ElasticSuiteCore\Block\Adminhtml\Relevance\Store;

use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Switcher extends \Magento\Backend\Block\Template
{
    /**
     * Name of container variable
     *
     * @var string
     */
    protected $defaultContainerVar = 'container';

    /**
     * Name of store variable
     *
     * @var string
     */
    protected $defaultStoreVarName = 'store';

    /**
     * @var array
     */
    protected $storeIds;

    /**
     * @var bool
     */
    protected $hasDefaultOption = true;

    /**
     * Container factory
     *
     * @var \Magento\Store\Model\ContainerFactory
     */
    protected $containerFactory;

    /**
     * Store Factory
     *
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    protected $requestConfiguration;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\ContainerFactory $containerFactory
     * @param \Magento\Store\Model\GroupFactory $storeGroupFactory
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        //\Magento\Store\Model\ContainerFactory $containerFactory,
        \Magento\Store\Model\GroupFactory $storeGroupFactory,
        \Magento\Store\Model\StoreFactory $storeFactory,
        RequestContainerInterface $requestConfiguration,
        array $data = []
    ) {
        parent::__construct($context, $data);
        //$this->containerFactory = $containerFactory;
        $this->requestConfiguration = $requestConfiguration;
        $this->storeGroupFactory = $storeGroupFactory;
        $this->storeFactory = $storeFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setUseConfirm(true);
        $this->setUseAjax(true);

        $this->setShowManageStoresLink(0);

        if (!$this->hasData('switch_containers')) {
            $this->setSwitchContainers(false);
        }
        if (!$this->hasData('switch_store_views')) {
            $this->setSwitchStoreViews(true);
        }

        $this->setDefaultSelectionName(__('All Store Views'));
    }

    /**
     * Get containers
     *
     * @return \Magento\Store\Model\Container[]
     */
    public function getContainers()
    {
        $containers = $this->requestConfiguration->getContainers();
        /*if ($containerIds = $this->getContainerIds()) {
            foreach (array_keys($containers) as $containerId) {
                if (!in_array($containerId, $containerIds)) {
                    unset($containers[$containerId]);
                }
            }
        }*/
        return $containers;
    }

    /**
     * Check if can switch to containers
     *
     * @return bool
     */
    public function isContainerSwitchEnabled()
    {
        return (bool)$this->getData('switch_containers');
    }

    /**
     * @param string $varName
     * @return $this
     */
    public function setContainerVarName($varName)
    {
        $this->setData('container_var_name', $varName);
        return $this;
    }

    /**
     * @return string
     */
    public function getContainerVarName()
    {
        if ($this->hasData('container_var_name')) {
            return (string)$this->getData('container_var_name');
        } else {
            return (string)$this->defaultContainerVar;
        }
    }

    /**
     * @param \Magento\Store\Model\Container $container
     * @return bool
     */
    public function isContainerSelected(array $container)
    {
        return $this->getContainerCode() === $container['name'] && $this->getStoreId() === null;
    }

    /**
     * @return int|null
     */
    public function getContainerCode()
    {
        if (!$this->hasData('container_code')) {
            $this->setData('container_code', (string) $this->getRequest()->getParam($this->getContainerVarName()));
        }
        return $this->getData('container_code');
    }

    public function getContainerName($container)
    {
        return $container["name"];
    }

    /**
     * @param \Magento\Store\Model\Group|int $group
     * @return \Magento\Store\Model\ResourceModel\Store\Collection
     */
    public function getStoreCollection($group)
    {
        if (!$group instanceof \Magento\Store\Model\Group) {
            $group = $this->_storeGroupFactory->create()->load($group);
        }
        $stores = $group->getStoreCollection();
        $_storeIds = $this->getStoreIds();
        if (!empty($_storeIds)) {
            $stores->addIdFilter($_storeIds);
        }
        return $stores;
    }

    /**
     * Get store views
     *
     * @return \Magento\Store\Model\Store[]
     */
    public function getStores()
    {
        $stores = $this->_storeManager->getStores();

        if ($storeIds = $this->getStoreIds()) {
            foreach (array_keys($stores) as $storeId) {
                if (!in_array($storeId, $storeIds)) {
                    unset($stores[$storeId]);
                }
            }
        }

        return $stores;
    }

    /**
     * @return int|null
     */
    public function getStoreId()
    {
        if (!$this->hasData('store_id')) {
            $this->setData('store_id', (int)$this->getRequest()->getParam($this->getStoreVarName()));
        }
        return $this->getData('store_id');
    }

    /**
     * @param \Magento\Store\Model\Store $store
     * @return bool
     */
    public function isStoreSelected(\Magento\Store\Model\Store $store)
    {
        return $this->getStoreId() !== null && (int)$this->getStoreId() === (int)$store->getId();
    }

    /**
     * Check if can switch to store views
     *
     * @return bool
     */
    public function isStoreSwitchEnabled()
    {
        return (bool)$this->getData('switch_store_views');
    }

    /**
     * @param string $varName
     * @return $this
     */
    public function setStoreVarName($varName)
    {
        $this->setData('store_var_name', $varName);
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getStoreVarName()
    {
        if ($this->hasData('store_var_name')) {
            return (string)$this->getData('store_var_name');
        } else {
            return (string)$this->defaultStoreVarName;
        }
    }

    /**
     * @return string
     */
    public function getSwitchUrl()
    {
        if ($url = $this->getData('switch_url')) {
            return $url;
        }
        return $this->getUrl(
            '*/*/*',
            [
                '_current' => true,
                $this->getStoreVarName() => null,
                $this->getStoreGroupVarName() => null,
                $this->getContainerVarName() => null,
            ]
        );
    }

    /**
     * @return bool
     */
    public function hasScopeSelected()
    {
        return $this->getStoreId() !== null || $this->getStoreGroupId() !== null || $this->getContainerId() !== null;
    }

    /**
     * Get current selection name
     *
     * @return string
     */
    public function getCurrentSelectionName()
    {
        if (!($name = $this->getCurrentStoreName())) {
            if (!($name = $this->getCurrentContainerName())) {
                $name = $this->getDefaultSelectionName();
            }
        }

        return $name;
    }

    /**
     * Get current container name
     *
     * @return string
     */
    public function getCurrentContainerName()
    {
        if ($this->getContainerCode() != null) {
            $container = $this->requestConfiguration->getContainer($this->getContainerCode());

            if ($this->getContainerName($container)) {
                return $this->getContainerName($container);
            }
        }
    }

    /**
     * Get current store view name
     *
     * @return string
     */
    public function getCurrentStoreName()
    {
        if ($this->getStoreId() !== null) {
            $store = $this->storeFactory->create();
            $store->load($this->getStoreId());
            if ($store->getId()) {
                return $store->getName();
            }
        }
    }

    /**
     * @param array $storeIds
     * @return $this
     */
    public function setStoreIds($storeIds)
    {
        $this->_storeIds = $storeIds;
        return $this;
    }

    /**
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return bool
     */
    public function isShow()
    {
        return true;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->isShow()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Set/Get whether the switcher should show default option
     *
     * @param bool $hasDefaultOption
     * @return bool
     */
    public function hasDefaultOption($hasDefaultOption = null)
    {
        if (null !== $hasDefaultOption) {
            $this->_hasDefaultOption = $hasDefaultOption;
        }
        return $this->hasDefaultOption;
    }

    /**
     * Get whether iframe is being used
     *
     * @return bool
     */
    public function isUsingIframe()
    {
        if ($this->hasData('is_using_iframe')) {
            return (bool)$this->getData('is_using_iframe');
        }
        return false;
    }
}
