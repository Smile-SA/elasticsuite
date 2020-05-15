<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Resolver;

use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Scope\ContainerFactory as ContainerScopeFactory;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\State\InitException;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig;

/**
 * Relevance Configuration Scope Resolver
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Containers implements ScopeResolverInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig
     */
    private $baseConfig;

    /**
     * @var \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Scope\ContainerFactory
     */
    private $containerScopeFactory;

    /**
     * Constructor.
     *
     * @param BaseConfig            $baseConfig            The base configuration
     * @param ContainerScopeFactory $containerScopeFactory Container Scope Factory
     */
    public function __construct(
        BaseConfig $baseConfig,
        ContainerScopeFactory $containerScopeFactory
    ) {
        $this->baseConfig            = $baseConfig;
        $this->containerScopeFactory = $containerScopeFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InitException
     */
    public function getScope($scopeId = null)
    {
        $container = $this->baseConfig->get($scopeId, []);

        if (null === $container) {
            throw new InitException(__('The scope object is invalid. Verify the scope object and try again.'));
        }

        return $this->containerToScope($scopeId, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes()
    {
        $scopes = [];

        foreach ($this->baseConfig->get() as $containerName => $container) {
            $scopes[] = $this->containerToScope($containerName, $container);
        }

        return $scopes;
    }

    /**
     * Build a \Magento\Framework\App\ScopeInterface from a search request container configuration
     *
     * @param string $containerName Container Name
     * @param array  $containerData Container Data
     *
     * @return \Magento\Framework\App\ScopeInterface
     */
    private function containerToScope($containerName, $containerData = [])
    {
        return $this->containerScopeFactory->create(
            [
                'data' => [
                    'code'            => $containerName,
                    'scope_type'      => 'containers',
                    'scope_type_name' => 'Search Container',
                    'name'            => $containerData['label'] ?? $containerName,
                ],
            ]
        );
    }
}
