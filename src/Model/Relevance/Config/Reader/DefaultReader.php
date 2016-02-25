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
use Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data\Collection\ScopedFactory;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * Default level Relevance Configuration Reader
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DefaultReader implements \Magento\Framework\App\Config\Scope\ReaderInterface
{
    /**
     * @var Initial
     */
    protected $initialConfig;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var \Smile\ElasticSuiteCore\Model\ResourceModel\Relevance\Config\Data\Collection\ScopedFactory
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
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
        $this->containerInterface = $containerInterface;
    }

    /**
     * Read configuration data
     *
     * @param null|string $scope The current scope to load (default)
     *
     * @return array Exception is thrown when scope other than default is given
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function read($scope = null)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $scope = $scope === null ? RequestContainerInterface::SCOPE_TYPE_DEFAULT : $scope;
        if ($scope !== RequestContainerInterface::SCOPE_TYPE_DEFAULT) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Only default scope allowed"));
        }

        $config = $this->initialConfig->getData($scope);

        $collection = $this->collectionFactory->create(
            ['scope' => $scope]
        );

        $logger->info("ITS ME THE READER ---> DEFAULT");

        $dbDefaultConfig = [];
        foreach ($collection as $item) {
            $dbDefaultConfig[$item->getPath()] = $item->getValue();
        }

        $dbDefaultConfig = $this->converter->convert($dbDefaultConfig);
        $config = array_replace_recursive($config, $dbDefaultConfig);

        $logger->info(print_r($config['smile_elasticsuite_relevance'], true));
        $logger->info("THAT WAS THE DEFAULT READER");

        return $config;
    }
}
