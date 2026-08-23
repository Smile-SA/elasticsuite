<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Plugin\Index\Indices\Mapping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Index\Mapping;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;

/**
 * Plugin that applies the configured text scoring algorithm/similarity on default/collector fields.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DefaultFieldsSimilarityPlugin
{
    /**
     * @var string
     */
    // phpcs:ignore Generic.Files.LineLength
    const COLLECTOR_FIELDS_SIMILARITY_XML_PATH = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/collector_fields_scoring_algorithm';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig Scope configuration.
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * After plugin - iterates over the default/collector fields and fixes their similarity if need be.
     *
     * @param Mapping $subject Index Mapping
     * @param array   $result  Mapping properties
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProperties(Mapping $subject, array $result): array
    {
        $similarity = $this->getConfiguredSimilarity();
        if (FieldInterface::SIMILARITY_DEFAULT !== $similarity) {
            $analyzers = $this->getDefaultSearchFieldsAnalyzers();
            foreach ($this->getDefaultSearchFields() as $defaultSearchField) {
                if (array_key_exists($defaultSearchField, $result)) {
                    $result[$defaultSearchField]['similarity'] = $similarity;
                    foreach ($analyzers as $analyzer) {
                        if (array_key_exists('fields', $result[$defaultSearchField])
                            && array_key_exists($analyzer, $result[$defaultSearchField]['fields'])
                        ) {
                            $result[$defaultSearchField]['fields'][$analyzer]['similarity'] = $similarity;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get the list of default/collector fields to look into.
     *
     * @return array
     */
    private function getDefaultSearchFields()
    {
        return [
            MappingInterface::DEFAULT_SEARCH_FIELD,
            MappingInterface::DEFAULT_SPELLING_FIELD,
            MappingInterface::DEFAULT_AUTOCOMPLETE_FIELD,
            MappingInterface::DEFAULT_REFERENCE_FIELD,
            MappingInterface::DEFAULT_EDGE_NGRAM_FIELD,
        ];
    }

    /**
     * Get the list of possible text analyzers for default/collector fields to apply the configured similarity to.
     *
     * @return array
     */
    private function getDefaultSearchFieldsAnalyzers()
    {
        return [
            FieldInterface::ANALYZER_STANDARD,
            FieldInterface::ANALYZER_WHITESPACE,
            FieldInterface::ANALYZER_SHINGLE,
            FieldInterface::ANALYZER_PHONETIC,
            FieldInterface::ANALYZER_REFERENCE,
            FieldInterface::ANALYZER_EDGE_NGRAM,
        ];
    }

    /**
     * Get the configured similarity for collector fields.
     *
     * @return string
     */
    private function getConfiguredSimilarity()
    {
        return ($this->scopeConfig->getValue(self::COLLECTOR_FIELDS_SIMILARITY_XML_PATH) ?? FieldInterface::SIMILARITY_DEFAULT);
    }
}
