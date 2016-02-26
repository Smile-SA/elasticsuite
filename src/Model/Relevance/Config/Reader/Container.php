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
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;
use Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data\Collection\ScopedFactory;

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
     * @var \Magento\Framework\App\Config\Scope\Converter
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
     * @var DefaultReader
     */
    protected $defaultReader;

    /**
     * @param Initial                   $initialConfig      Initial Configuration
     * @param Converter                 $converter          Configuration Converter
     * @param ScopedFactory             $collectionFactory  Configuration Collection Factory
     * @param RequestContainerInterface $containerInterface Request Containers interface
     * @param DefaultReader             $defaultReader      The default reader
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory,
        RequestContainerInterface $containerInterface,
        DefaultReader $defaultReader
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
        $this->containerInterface = $containerInterface;
        $this->defaultReader = $defaultReader;
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

        $config = array_replace_recursive(
            $this->defaultReader->read(RequestContainerInterface::SCOPE_TYPE_DEFAULT),
            $this->initialConfig->getData("containers|{$code}")
        );

        $collection = $this->collectionFactory->create(
            ['scope' => RequestContainerInterface::SCOPE_CONTAINERS, 'scopeCode' => $code]
        );

        $logger->info("ITS ME THE READER ---> CONTAINER");

        $dbContainerConfig = [];
        foreach ($collection as $item) {
            $dbContainerConfig[$item->getPath()] = $item->getValue();
        }

        $dbContainerConfig = $this->converter->convert($dbContainerConfig);

        if (count($dbContainerConfig)) {
            $config = array_replace_recursive($config, $dbContainerConfig);
        }

        $logger->info(print_r($config['smile_elasticsuite_relevance'], true));
        $logger->info("THAT WAS THE CONTAINER READER");

        return $config;
    }
}
