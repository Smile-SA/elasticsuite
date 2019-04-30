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

use Magento\Framework\App\Config\Scope\Converter;
use Smile\ElasticsuiteCore\Model\ResourceModel\Search\Request\RelevanceConfig\Data\Collection\ScopedFactory;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Initial;

/**
 * Default level Relevance Configuration Reader
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @var ScopedFactory
     */
    protected $collectionFactory;

    /**
     * @param Initial       $initialConfig     Initial Configuration
     * @param Converter     $converter         Configuration Converter
     * @param ScopedFactory $collectionFactory Configuration Collection Factory
     */
    public function __construct(
        Initial $initialConfig,
        Converter $converter,
        ScopedFactory $collectionFactory
    ) {
        $this->initialConfig = $initialConfig;
        $this->converter = $converter;
        $this->collectionFactory = $collectionFactory;
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
        $scope = $scope === null ? ContainerScopeInterface::SCOPE_DEFAULT : $scope;
        if ($scope !== ContainerScopeInterface::SCOPE_DEFAULT) {
            throw new \Magento\Framework\Exception\LocalizedException(__("Only default scope allowed"));
        }

        $config = $this->initialConfig->getData($scope);

        $collection = $this->collectionFactory->create(
            ['scope' => $scope]
        );

        $dbDefaultConfig = [];
        foreach ($collection as $item) {
            $dbDefaultConfig[$item->getPath()] = $item->getValue();
        }

        $dbDefaultConfig = $this->converter->convert($dbDefaultConfig);
        $config = array_replace_recursive($config, $dbDefaultConfig);

        return $config;
    }
}
