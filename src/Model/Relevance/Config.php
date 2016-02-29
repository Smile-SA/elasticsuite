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

namespace Smile\ElasticSuiteCore\Model\Relevance;

use Magento\Config\Model\Config\Loader;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticSuiteCore\Api\Config\SearchRequestContainerInterface;

/**
 * Relevance Configuration Model
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Config extends \Magento\Config\Model\Config
{
    /**
     * @var SearchRequestContainerInterface
     */
    protected $requestConfiguration;

    /**
     * @var bool If getting full config or not
     */
    protected $fullConfig;

    /**
     * Class constructor
     *
     * @param ReinitableConfigInterface       $config               Configuration interface
     * @param ManagerInterface                $eventManager         Event Manager
     * @param Structure                       $configStructure      Configuration Structure
     * @param TransactionFactory              $transactionFactory   Transaction Factory
     * @param Loader                          $configLoader         Configuration Loader
     * @param ValueFactory                    $configValueFactory   Configuration Value Factory
     * @param StoreManagerInterface           $storeManager         Store Manager
     * @param SearchRequestContainerInterface $requestConfiguration Request containers interface
     * @param array                           $data                 The data
     */
    public function __construct(
        ReinitableConfigInterface $config,
        ManagerInterface $eventManager,
        Structure $configStructure,
        TransactionFactory $transactionFactory,
        Loader $configLoader,
        ValueFactory $configValueFactory,
        StoreManagerInterface $storeManager,
        SearchRequestContainerInterface $requestConfiguration,
        array $data = []
    ) {
        $this->requestConfiguration = $requestConfiguration;
        $this->fullConfig           = true;
        parent::__construct(
            $config,
            $eventManager,
            $configStructure,
            $transactionFactory,
            $configLoader,
            $configValueFactory,
            $storeManager,
            $data
        );
    }

    /**
     * @return boolean
     */
    public function isFullConfig()
    {
        return $this->fullConfig;
    }

    /**
     * Set flag to load full config or not
     *
     * @param boolean $fullConfig If we should grab full config or not
     *
     * @return Config
     */
    public function setFullConfig($fullConfig)
    {
        $this->fullConfig = $fullConfig;

        return $this;
    }

    /**
     * Save config section
     * Require set: section, website, store and groups
     *
     * @throws \Exception
     * @return $this
     */
    public function save()
    {
        $this->initScope();

        $sectionId = $this->getSection();
        $groups = $this->getGroups();
        if (empty($groups)) {
            return $this;
        }

        $oldConfig = $this->getConfig();

        $deleteTransaction = $this->_transactionFactory->create();
        $saveTransaction = $this->_transactionFactory->create();

        $extraOldGroups = [];

        foreach ($groups as $groupId => $groupData) {
            $this->_processGroup(
                $groupId,
                $groupData,
                $groups,
                $sectionId,
                $extraOldGroups,
                $oldConfig,
                $saveTransaction,
                $deleteTransaction
            );
        }

        try {
            $deleteTransaction->delete();
            $saveTransaction->save();
            $this->_appConfig->reinit();
            $this->_eventManager->dispatch(
                "admin_system_config_changed_section_{$this->getSection()}",
                ['website' => $this->getWebsite(), 'store' => $this->getStore()]
            );
        } catch (\Exception $e) {
            $this->_appConfig->reinit();
            throw $e;
        }

        return $this;
    }

    /**
     * Load config data for section
     *
     * @return array
     */
    public function load()
    {
        if ($this->_configData === null) {
            $this->initScope();
            $this->setFullConfig(false);
            $this->_configData = $this->_getConfig();
            $this->setFullConfig(true);
        }

        return $this->_configData;
    }

    /**
     * Return formatted config data for current section
     *
     * @return array
     */
    // @codingStandardsIgnoreStart Method is inherited
    protected function getConfig()
    {
        // @codingStandardsIgnoreEnd

        return $this->_configLoader->getConfigByPath(
            $this->getSection(),
            $this->getScope(),
            $this->getScopeCode(),
            $this->isFullConfig()
        );
    }

    /**
     * Get scope name and scopeId
     *
     * @return void
     */
    private function initScope()
    {
        if ($this->getSection() === null) {
            $this->setSection('');
        }
        if ($this->getContainer() === null) {
            $this->setContainer('');
        }
        if ($this->getStore() === null) {
            $this->setStore('');
        }

        $scope = 'default';
        $scopeCode = 'default';

        if ($this->getStore()) {
            $scope = 'containers_stores';
            $store = $this->_storeManager->getStore($this->getStore());
            $scopeCode = $store->getId();
            if ($this->getContainer() && ($this->getContainer() != "")) {
                $scopeCode = $this->getContainer() . "|" . $scopeCode;
            }
        } elseif ($this->getContainer()) {
            $scope = 'containers';
            $container = $this->requestConfiguration->getContainer($this->getContainer());
            $scopeCode = $container['name'];
        }

        $this->setScope($scope);
        $this->setScopeCode($scopeCode);
    }
}
