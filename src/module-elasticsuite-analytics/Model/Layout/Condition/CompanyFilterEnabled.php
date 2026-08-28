<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Layout\Condition;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Layout\Condition\VisibilityConditionInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Layout visibility condition gating the search usage dashboard's company filter uiComponent
 * - on the module's own "Show Company filter" toggle
 *   (in addition to the ifconfig="btob/website_configuration/company_active" setting)
 * - on the fact that Magento_Company is actually enabled/present
 *   (in case everything was installed, enabled and then removed)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CompanyFilterEnabled implements VisibilityConditionInterface
{
    /**
     * Unique name, referenced by <visibilityCondition name="...">.
     */
    const NAME = 'company_filter_enabled';

    /**
     * Configuration path for enabling or disabling the Company filter.
     */
    const COMPANY_FILTER_ENABLED_XPATH = 'smile_elasticsuite_analytics/filters_configuration/company_enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig   Scope configuration.
     * @param ModuleManager        $moduleManager Module manager.
     */
    public function __construct(ScopeConfigInterface $scopeConfig, ModuleManager $moduleManager)
    {
        $this->scopeConfig = $scopeConfig;
        $this->moduleManager = $moduleManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible(array $arguments)
    {
        return $this->moduleManager->isEnabled('Magento_Company')
            && interface_exists('\Magento\Company\Api\CompanyRepositoryInterface')
            && $this->scopeConfig->isSetFlag(self::COMPANY_FILTER_ENABLED_XPATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
