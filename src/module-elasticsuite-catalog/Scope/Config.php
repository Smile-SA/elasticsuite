<?php
namespace Smile\ElasticsuiteCatalog\Scope;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    protected const INCLUDE_CHILD_ATTRIBUTES = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/include_child_attributes';

    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isIncludeChildAttributes(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::INCLUDE_CHILD_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE
        );
    }
}
