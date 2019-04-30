<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Action;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\FullFactory as DefaultFactory;

/**
 * Custom factory written to be able to load different indexers depending of the configured search enginge.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
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
     * @param \Magento\Framework\ObjectManagerInterface $objectManager  Object manager.
     * @param \Magento\Framework\ObjectManagerInterface $scopeConfig    Configuration.
     * @param string                                    $configPath     Search engine config path.
     * @param array                                     $fullActionPool List of indexers class by engine.
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        $configPath = EngineInterface::CONFIG_ENGINE_PATH,
        $fullActionPool = []
    ) {
        $this->objectManager  = $objectManager;
        $this->scopeConfig    = $scopeConfig;
        $this->configPath     = $configPath;
        $this->fullActionPool = $fullActionPool;
    }

    /**
     * Create an indexer using the right class depending of the configuration.
     *
     * @param array $data Data passed to the indexer while it is instantiated.
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
