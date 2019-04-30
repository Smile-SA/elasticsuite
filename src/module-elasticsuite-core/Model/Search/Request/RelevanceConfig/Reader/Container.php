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

use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial;
use Magento\Framework\App\Config\Scope\Converter;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data\Collection\ScopedFactory;

/**
 * Container's scope configuration reader for relevance configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Container implements \Magento\Framework\App\Config\Scope\ReaderInterface
{
    /**
     * @var Initial
     */
    protected $initialConfig;



    /**
     * @var \Magento\Framework\App\Config\Scope\Converter
     */
    protected $converter;

    /**
     * @var ScopedFactory
     */
    protected $collectionFactory;

    /**
     * @var DefaultReader
     */
    protected $defaultReader;

    /**
     * @param Initial       $initialConfig     Initial Configuration
     * @param Converter     $converter         Configuration Converter
     * @param ScopedFactory $collectionFactory Configuration Collection Factory
     * @param DefaultReader $defaultReader     The default reader
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory,
        DefaultReader $defaultReader
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
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
        $config = array_replace_recursive(
            $this->defaultReader->read(ContainerScopeInterface::SCOPE_DEFAULT),
            $this->initialConfig->getData($code)
        );

        $collection = $this->collectionFactory->create(
            ['scope' => ContainerScopeInterface::SCOPE_CONTAINERS, 'scopeCode' => $code]
        );

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
