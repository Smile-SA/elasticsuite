<?php
/**
 * DISCLAIMER
 *
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
use Magento\Store\Model\ResourceModel\Config\Collection\ScopedFactory;
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
     * @var \Magento\Framework\App\Config\ScopePool
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
     * @param Converter                 $converter          Configuration Converter
     * @param ScopedFactory             $collectionFactory  Configuration Collection Factory
     * @param RequestContainerInterface $containerInterface Request Containers interface
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory,
        RequestContainerInterface $containerInterface
    ) {
        $this->initialConfig       = $initialConfig;
        $this->converter           = $converter;
        $this->collectionFactory   = $collectionFactory;
        $this->containerInterface = $containerInterface;
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
        // @TODO Proper container retrieval with a correct code
        //$container = $this->requestConfiguration->getContainer($code);
        $config    = $this->initialConfig->getData(RequestContainerInterface::SCOPE_CONTAINERS);

        $collection = $this->collectionFactory->create(
            ['scope' => RequestContainerInterface::SCOPE_CONTAINERS, 'scopeId' => 99]
        );

        $dbContainerConfig = [];
        foreach ($collection as $item) {
            $dbContainerConfig[$item->getPath()] = $item->getValue();
        }

        $dbContainerConfig = $this->converter->convert($dbContainerConfig);
        $config = array_replace_recursive($config, $dbContainerConfig);

        return $config;
    }
}
