<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Dan Wallis <dan@wallis.nz>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Scope;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration retreival class
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Dan Wallis <dan@wallis.nz>
 */
class Config
{
    protected const INCLUDE_CHILD_ATTRIBUTES = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/include_child_attributes';

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig Magento configuration class
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve configuration option to include/exclude child attributes from indexing
     *
     * @return bool
     */
    public function isIncludeChildAttributes(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::INCLUDE_CHILD_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );
    }
}
