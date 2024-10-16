<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Smile Elasticsuite virtual category config helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Config
{
    const XML_PATH_FORCE_ZERO_RESULTS_FOR_DISABLED_CATEGORIES = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/' .
    'force_zero_results_for_disabled_categories';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig Scope configuration.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns true if categories and search queries positions should be ignored for Out of Stock products.
     *
     * @param integer $storeId Store id
     *
     * @return bool
     */
    public function isForceZeroResultsForDisabledCategoriesEnabled($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_FORCE_ZERO_RESULTS_FOR_DISABLED_CATEGORIES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
