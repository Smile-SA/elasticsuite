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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator;

/**
 * Optimizer Registry Locator.
 * Used by Ui Component modifiers.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RegistryLocator implements LocatorInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var null
     */
    private $optimizer = null;

    /**
     * RegistryLocator constructor.
     *
     * @param \Magento\Framework\Registry $registry The registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizer()
    {
        if (null !== $this->optimizer) {
            return $this->optimizer;
        }

        if ($optimizer = $this->registry->registry('current_optimizer')) {
            return $this->optimizer = $optimizer;
        }

        return null;
    }
}
