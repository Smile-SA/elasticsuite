<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;

/**
 * Checks that, verify if Magento is correctly configured to use Elasticsuite as the default search engine.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class SearchEngineConfigCheck extends AbstractCheck
{
    /**
     * Configuration path for the search engine setting.
     */
    private const CONFIG_PATH_SEARCH_ENGINE = 'catalog/search/engine';

    /**
     * Expected value for Elasticsuite.
     */
    private const ELASTICSUITE_ENGINE = 'elasticsuite';

    /**
     * Route to Stores -> Configuration section.
     */
    private const ROUTE_SYSTEM_CONFIG = 'adminhtml/system_config/edit';

    /**
     * Anchor for Stores -> Configuration -> Catalog -> Catalog Search.
     */
    private const ANCHOR_CATALOG_SEARCH_CONFIG = 'catalog_search-link';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope configuration.
     * @param UrlInterface         $urlBuilder  URL builder.
     * @param int                  $sortOrder   Sort order (default: 50).
     * @param int                  $severity    Severity level.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        int $sortOrder = 50,
        int $severity = MessageInterface::SEVERITY_CRITICAL
    ) {
        parent::__construct($urlBuilder, $sortOrder, $severity);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return 'search_engine_config_check';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        $description = __('Magento is correctly configured to use Elasticsuite as its search engine.');

        if ($this->getStatus() === CheckInterface::STATUS_FAILED) {
            // @codingStandardsIgnoreStart
            $description = implode(
                '<br />',
                [
                    __(
                        'Magento is not configured to use Elasticsuite as its search engine.'
                    ),
                    __(
                        'Please click <a href="%1"><strong>here</strong></a> to head to the <strong>Catalog Search Configuration</strong> and select ElasticSuite in the <strong>Search Engine</strong> field.',
                        $this->getCatalogSearchConfigUrl()
                    ),
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        return $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus(): string
    {
        $configuredEngine = $this->scopeConfig->getValue(self::CONFIG_PATH_SEARCH_ENGINE);

        return ($configuredEngine === self::ELASTICSUITE_ENGINE) ? CheckInterface::STATUS_PASSED : CheckInterface::STATUS_FAILED;
    }

    /**
     * Get URL to the Catalog Search Configuration page.
     *
     * @return string
     */
    private function getCatalogSearchConfigUrl(): string
    {
        return $this->urlBuilder->getUrl(
            self::ROUTE_SYSTEM_CONFIG,
            ['section' => 'catalog', '_fragment' => self::ANCHOR_CATALOG_SEARCH_CONFIG]
        );
    }
}
