<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Plugin\Index\IndexSettings;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;

/**
 * Plugin to apply the configured tokenizer to the shingle analyzer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class ShingleTokenizerPlugin
{
    const SHINGLE_TOKENIZER_XML_PATH = 'shingle_analyzer/tokenizer';

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope config.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Apply the configured tokenizer to the shingle analyzer.
     *
     * @param IndexSettingsInterface        $subject Index settings.
     * @param array|null                    $result  Analysis settings.
     * @param integer|string|StoreInterface $store   Store.
     *
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAnalysisSettings(IndexSettingsInterface $subject, $result, $store)
    {
        $tokenizer = $this->getTokenizer($store);

        if (!empty($tokenizer) && isset($result['analyzer'][FieldInterface::ANALYZER_SHINGLE])) {
            $result['analyzer'][FieldInterface::ANALYZER_SHINGLE]['tokenizer'] = $tokenizer;
        }

        return $result;
    }

    /**
     * Return the tokenizer configured for the shingle analyzer for the given store.
     *
     * @param integer|string|StoreInterface $store Store.
     *
     * @return string|null
     */
    private function getTokenizer($store)
    {
        return $this->scopeConfig->getValue(
            IndexSettingsHelper::ANALYSIS_CONFIG_XML_PREFIX . '/' . self::SHINGLE_TOKENIZER_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
