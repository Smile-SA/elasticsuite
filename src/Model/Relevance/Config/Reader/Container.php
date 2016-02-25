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
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Reader;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Config\Scope\Converter;
use Magento\Framework\App\Config\ScopePool;
use Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data\Collection\ScopedFactory;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * Container's scope configuration reader for relevance configuration
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Container implements \Magento\Framework\App\Config\Scope\ReaderInterface
{
    /**
     * @var Initial
     */
    protected $initialConfig;

    /**
     * @var ScopePool
     */
    protected $scopePool;

    /**
     * @var \Magento\Store\Model\Config\Converter
     */
    protected $converter;

    /**
     * @var ScopedFactory
     */
    protected $collectionFactory;

    /**
     * @var RequestContainerInterface
     */
    protected $containerInterface;

    /**
     * @param Initial                   $initialConfig      Initial Configuration
     * @param ScopePool                 $scopePool          Scoped Configuration reader
     * @param Converter                 $converter          Configuration Converter
     * @param ScopedFactory             $collectionFactory  Configuration Collection Factory
     * @param RequestContainerInterface $containerInterface Request Containers interface
     */
    public function __construct(
        Initial $initialConfig,
        ScopePool $scopePool,
        Converter $converter,
        ScopedFactory $collectionFactory,
        RequestContainerInterface $containerInterface
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
        $this->containerInterface = $containerInterface;
        $this->scopePool = $scopePool;
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
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info(get_class($this));

        $config = array_replace_recursive(
            $this->scopePool->getScope(RequestContainerInterface::SCOPE_TYPE_DEFAULT)->getSource(),
            $this->initialConfig->getData("containers|{$code}")
        );

        $collection = $this->collectionFactory->create(
            ['scope' => RequestContainerInterface::SCOPE_CONTAINERS, 'scopeCode' => $code]
        );

        $logger->info("ITS ME THE READER ---> STORE");

        $dbContainerConfig = [];
        foreach ($collection as $item) {
            $dbContainerConfig[$item->getPath()] = $item->getValue();
        }

        $dbContainerConfig = $this->converter->convert($dbContainerConfig);

        if (count($dbContainerConfig)) {
            $config = array_replace_recursive($config, $dbContainerConfig);
        }

        return $config;
    }
}
