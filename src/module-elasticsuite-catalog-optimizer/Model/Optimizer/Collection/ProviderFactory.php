<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Collection;

/**
 * Optimizer Collection Provider Factory.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ProviderFactory
{
    /**
     * @var array
     */
    private $factories;

    /**
     * Constructor.
     *
     * @param array $factories Provider factories by type.
     */
    public function __construct($factories = [])
    {
        $this->factories = $factories;
    }

    /**
     * Create an Optimizer Collection provider from it's type and params.
     *
     * @param string $providerType   Provider type (must be a valid query type defined into the factories array).
     * @param array  $providerParams Provider constructor params.
     *
     * @return ProviderInterface
     */
    public function create($providerType, $providerParams = [])
    {
        if (!isset($this->factories[$providerType])) {
            throw new \LogicException("No factory found for provider of type {$providerType}");
        }

        return $this->factories[$providerType]->create($providerParams);
    }
}
