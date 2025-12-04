<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * Thesaurus stemming configuration helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class ThesaurusStemmingConfig
{
    /** @var string */
    const THESAURUS_ANALYSIS_USE_STEMMING_XML_PATH = 'smile_elasticsuite_thesaurus_settings/analysis/use_stemming';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope config interface.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns true if stemming should be used for synonyms and expansions matching.
     *
     * @param integer $storeId Store id.
     *
     * @return bool
     */
    public function useStemming($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::THESAURUS_ANALYSIS_USE_STEMMING_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
