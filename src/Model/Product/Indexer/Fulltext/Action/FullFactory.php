<?php

namespace Smile\ElasticSuiteCatalog\Model\Product\Indexer\Fulltext\Action;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\FullFactory as DefaultFactory;

class FullFactory extends DefaultFactory
{

    /**
     * @var \Magento\CatalogSearch\Model\Indexer\Fulltext\Action\Full
     */
    private $fullActionPool;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $configPath;

    /**
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\ObjectManagerInterface $scopeConfig
     * @param string                                    $configPath
     * @param array                                     $fullActionPool
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        $configPath = EngineInterface::CONFIG_ENGINE_PATH,
        $fullActionPool = []
    )
    {
        $this->objectManager  = $objectManager;
        $this->scopeConfig    = $scopeConfig;
        $this->configPath     = $configPath;
        $this->fullActionPool = $fullActionPool;
    }

    /**
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data = [])
    {
        $engine = $this->scopeConfig->getValue($this->configPath, ScopeInterface::SCOPE_STORE);

        $fullActionClass = $this->fullActionPool['default'];

        if (isset($this->fullActionPool[$engine])) {
            $fullActionClass = $this->fullActionPool[$engine];
        }

        return $this->objectManager->create($fullActionClass, ['data' => $data]);
    }
}