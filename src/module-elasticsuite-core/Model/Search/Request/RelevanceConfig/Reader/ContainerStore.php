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
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Reader;

use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial;
use Magento\Framework\App\Config\Scope\Converter;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data\Collection\ScopedFactory;

/**
 * Configuration reader for Store Container level : Configuration for a given container on a given store
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ContainerStore
{
    /**
     * @var Initial
     */
    protected $initialConfig;

    /**
     * @var \Magento\Store\Model\Config\Converter
     */
    protected $converter;

    /**
     * @var ScopedFactory
     */
    protected $collectionFactory;

    /**
     * @var Container
     */
    protected $containerReader;

    /**
     * Constructor
     *
     * @param Initial               $initialConfig     Initial Configuration
     * @param Converter             $converter         Configuration Converter
     * @param ScopedFactory         $collectionFactory Configuration Collection Factory
     * @param Container             $containerReader   Parent level configuration reader
     * @param StoreManagerInterface $storeManager      Magento Store Manager interface
     *
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory,
        Container $containerReader,
        StoreManagerInterface $storeManager
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
        $this->containerReader = $containerReader;
        $this->storeManager = $storeManager;
    }

    /**
     * Read configuration by code
     *
     * @param null|string $code The container code
     *
     * @return array
     */
    public function read($code = null)
    {
        list($containerCode, $storeId) = explode("|", $code);
        $store = $this->storeManager->getStore($storeId);

        $config = array_replace_recursive(
            $this->containerReader->read($containerCode),
            $this->initialConfig->getData("{$containerCode}|{$store->getCode()}")
        );

        $collection = $this->collectionFactory->create(
            ['scope' => ContainerScopeInterface::SCOPE_STORE_CONTAINERS, 'scopeCode' => $code]
        );

        $dbStoreConfig = [];
        foreach ($collection as $item) {
            $dbStoreConfig[$item->getPath()] = $item->getValue();
        }

        $dbStoreConfig = $this->converter->convert($dbStoreConfig);
        $config = array_replace_recursive($config, $dbStoreConfig);

        return $config;
    }
}
