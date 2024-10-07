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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Search\Request\RelevanceConfig\Scope;

use Magento\Backend\Block\Template;
use Smile\ElasticsuiteCore\Model\Search\Request\Source\Containers;

/**
 * Relevance configuration store switcher
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Switcher extends Template
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
     * @var boolean
     */
    protected $hasDefaultOption = true;

    /**
     * Store Factory
     *
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;

    /**
     * @var Containers
     */
    protected $containersSource;


    /**
     * Class constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context          Application context
     * @param \Magento\Store\Model\StoreFactory       $storeFactory     Store factory
     * @param Containers                              $containersSource The Containers source model
     * @param array                                   $data             The data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\StoreFactory $storeFactory,
        Containers $containersSource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->containersSource = $containersSource;
        $this->storeFactory = $storeFactory;
    }

    /**
     * Get containers
     *
     * @return array
     */
    public function getContainers()
    {
        $containers = $this->containersSource->getContainers();

        return $containers;
    }

    /**
     * Check if can switch to containers
     *
     * @return bool
     */
    public function isContainerSwitchEnabled()
    {
        return (bool) $this->getData('switch_containers');
    }

    /**
     * @param string $varName The var name
     *
     * @return $this
     */
    public function setContainerVarName($varName)
    {
        $this->setData('container_var_name', $varName);

        return $this;
    }

    /**
     * Check if container is selected
     *
     * @param array $container The container
     *
     * @return bool
     */
    public function isContainerSelected(array $container)
    {
        return $this->getContainerCode() === $container['name'];
    }

    /**
     * Retrieve container code
     *
     * @return string|null
     */
    public function getContainerCode()
    {
        if (!$this->hasData('container_code')) {
            $this->setData('container_code', $this->getRequest()->getParam($this->getContainerVarName()));
        }

        return $this->getData('container_code');
    }

    /**
     * Retrieve container var name
     *
     * @return string
     */
    public function getContainerVarName()
    {
        if ($this->hasData('container_var_name')) {
            return (string) $this->getData('container_var_name');
        }

        return (string) $this->defaultContainerVar;
    }

    /**
     * Retrieve Store Id
     *
     * @return int|null
     */
    public function getStoreId()
    {
        if (!$this->hasData('store_id')) {
            $this->setData('store_id', (int) $this->getRequest()->getParam($this->getStoreVarName()));
        }

        return $this->getData('store_id');
    }

    /**
     * Retrieve store var name
     *
     * @return mixed|string
     */
    public function getStoreVarName()
    {
        if ($this->hasData('store_var_name')) {
            return (string) $this->getData('store_var_name');
        }

        return (string) $this->defaultStoreVarName;
    }

    /**
     * Get store views
     *
     * @return \Magento\Store\Model\Store[]
     */
    public function getStores()
    {
        $stores = $this->_storeManager->getStores();

        return $stores;
    }

    /**
     * @param \Magento\Store\Model\Store $store The store
     *
     * @return bool
     */
    public function isStoreSelected(\Magento\Store\Model\Store $store)
    {
        return $this->getStoreId() !== null && (int) $this->getStoreId() === (int) $store->getId();
    }

    /**
     * Check if can switch to store views
     *
     * @return bool
     */
    public function isStoreSwitchEnabled()
    {
        return (bool) $this->getData('switch_store_views');
    }

    /**
     * @param string $varName The var name
     *
     * @return $this
     */
    public function setStoreVarName($varName)
    {
        $this->setData('store_var_name', $varName);

        return $this;
    }

    /**
     * Retrieve switch url
     *
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
                '_current'                    => true,
                $this->getStoreVarName()      => null,
                $this->getStoreGroupVarName() => null,
                $this->getContainerVarName()  => null,
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
        $name = $this->getDefaultSelectionName();

        if ($this->getCurrentContainerName()) {
            $name = $this->getCurrentContainerLabel();
            if ($this->getCurrentStoreName()) {
                $name .= " > " . $this->getCurrentStoreName();
            }
        }

        return $name;
    }

    /**
     * Get current store view name
     *
     * @return string
     */
    public function getCurrentStoreName()
    {
        $storeName = '';
        if ($this->getStoreId() !== null) {
            $store = $this->storeFactory->create();
            $store->load($this->getStoreId());
            if ($store->getId()) {
                $storeName = $store->getName();
            }
        }

        return $storeName;
    }

    /**
     * Get current container name
     *
     * @return string
     */
    public function getCurrentContainerName()
    {
        $containerName = '';
        if ($this->getContainerCode() !== null) {
            $container = $this->containersSource->get($this->getContainerCode());

            if ($this->getContainerName($container)) {
                $containerName = $this->getContainerName($container);
            }
        }

        return $containerName;
    }

    /**
     * Get container name
     *
     * @param array $container The container name
     *
     * @return mixed
     */
    public function getContainerName($container)
    {
        return $container["name"];
    }

    /**
     * Get current container name
     *
     * @return string
     */
    public function getCurrentContainerLabel()
    {
        $containerLabel = '';
        if ($this->getContainerCode() !== null) {
            $container = $this->containersSource->get($this->getContainerCode());

            if ($this->getContainerLabel($container)) {
                $containerLabel = $this->getContainerLabel($container);
            }
        }

        return $containerLabel;
    }

    /**
     * Get container name
     *
     * @param array $container The container name
     *
     * @return mixed
     */
    public function getContainerLabel($container)
    {
        return $container["label"];
    }

    /**
     * Check if container is used for fulltext queries
     *
     * @param array $container The container name
     *
     * @return bool
     */
    public function isFullText($container)
    {
        $fulltext = false;
        if (isset($container['fulltext'])) {
            $fulltext = filter_var($container['fulltext'], FILTER_VALIDATE_BOOLEAN);
        }

        return $fulltext;
    }

    /**
     * Set/Get whether the switcher should show default option
     *
     * @param bool $hasDefaultOption If witcher has default option
     *
     * @return bool
     */
    public function hasDefaultOption($hasDefaultOption = null)
    {
        if (null !== $hasDefaultOption) {
            $this->hasDefaultOption = $hasDefaultOption;
        }

        return $this->hasDefaultOption;
    }

    /**
     * Internal constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
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
}
