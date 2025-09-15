<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Model\Indexer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Synonym index handler.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexHandler
{
    /**
     * @var string
     */
    const THESAURUS_ANALYSIS_USE_STEMMING_XML_PATH = 'smile_elasticsuite_thesaurus_settings/analysis/use_stemming';

    /**
     * @var string
     */
    const STEMMER_FILTER = 'stemmer';

    /**
     * @var string
     */
    const STEMMER_BEFORE_FILTER = 'stemmer_before';

    /**
     * @var string
     */
    const STEMMER_AFTER_FILTER = 'stemmer_after';

    /**
     * @var string
     */
    const STEMMER_OVERRIDE_FILTER = 'stemmer_override';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var IndexOperationInterface
     */
    private $indexManager;

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * Constructor.
     *
     * @param ClientInterface         $client              ES client.
     * @param IndexOperationInterface $indexManager        ES index management tool
     * @param IndexSettingsHelper     $indexSettingsHelper Index settings helper.
     * @param IndexSettingsInterface  $indexSettings       Index settings provider.
     * @param ScopeConfigInterface    $scopeConfig         Scope config interface.
     * @param CacheHelper             $cacheHelper         ES caching helper.
     */
    public function __construct(
        ClientInterface $client,
        IndexOperationInterface $indexManager,
        IndexSettingsHelper $indexSettingsHelper,
        IndexSettingsInterface $indexSettings,
        ScopeConfigInterface $scopeConfig,
        CacheHelper $cacheHelper
    ) {
        $this->client              = $client;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexManager        = $indexManager;
        $this->indexSettings       = $indexSettings;
        $this->scopeConfig         = $scopeConfig;
        $this->cacheHelper         = $cacheHelper;
    }

    /**
     * Create the synonyms index for a store id.
     *
     * @param integer  $storeId    Store id.
     * @param string[] $synonyms   Raw synonyms list.
     * @param string[] $expansions Raw expansions list.
     *
     * @return void
     */
    public function reindex($storeId, $synonyms, $expansions)
    {
        $indexIdentifier = ThesaurusIndex::INDEX_IDENTIER;
        $indexName       = $this->indexSettingsHelper->createIndexNameFromIdentifier($indexIdentifier, $storeId);
        $indexAlias      = $this->indexSettingsHelper->getIndexAliasFromIdentifier($indexIdentifier, $storeId);
        $indexSettings   = ['settings' => $this->getIndexSettings($storeId, $synonyms, $expansions)];
        $this->client->createIndex($indexName, $indexSettings);
        $this->indexManager->proceedIndexInstall($indexName, $indexAlias);
        $this->cacheHelper->cleanIndexCache(ThesaurusIndex::INDEX_IDENTIER, $storeId);
    }

    /**
     * Returns index settings.
     *
     * @param integer  $storeId    Store id.
     * @param string[] $synonyms   Raw synonyms list.
     * @param string[] $expansions Raw expansions list.
     *
     * @return array
     */
    private function getIndexSettings($storeId, $synonyms, $expansions)
    {
        $indexIdentifier = ThesaurusIndex::INDEX_IDENTIER;

        $settings = [
            'number_of_shards'      => $this->indexSettingsHelper->getNumberOfShards($indexIdentifier),
            'number_of_replicas'    => $this->indexSettingsHelper->getNumberOfReplicas($indexIdentifier),
            'requests.cache.enable' => true,
        ];

        $settings['analysis']['filter']['shingle'] = [
            'type'             => 'shingle',
            'output_false'     => true,
            'token_separator'  => ThesaurusIndex::WORD_DELIMITER,
            'max_shingle_size' => ThesaurusIndex::MAX_SIZE,
        ];

        $settings['analysis']['filter']['analyze_shingle'] = [
            'type'             => 'shingle',
            'output_unigrams'  => false,
            'token_separator'  => ThesaurusIndex::WORD_DELIMITER,
            'min_shingle_size' => IndexSettingsHelper::MIN_SHINGLE_SIZE_DEFAULT,
            'max_shingle_size' => ThesaurusIndex::MAX_SIZE,
        ];

        $settings['max_shingle_diff'] = $this->indexSettingsHelper->getMaxShingleDiff($settings['analysis']);
        $settings['analysis']['filter']['type_filter'] = [
            'type' => 'keep_types',
            'types' => [ "SYNONYM" ],
        ];

        $stemmingFilters = [];
        if ($this->useStemming($storeId)) {
            $stemmingFilters = $this->getStemmingTokenFilters($storeId);
            if (!empty($stemmingFilters)) {
                $settings['analysis']['filter'] = array_merge($settings['analysis']['filter'], $stemmingFilters);
            }
        }

        $settings = $this->addShinglesAnalyzer($settings, array_keys($stemmingFilters));

        $settings = $this->addAnalyzerSettings($settings, 'synonym', $synonyms, array_keys($stemmingFilters));
        $settings = $this->addAnalyzerSettings($settings, 'expansion', $expansions, array_keys($stemmingFilters));

        return $settings;
    }

    /**
     * Returns true if stemming should be used for synonyms and expansions matching.
     *
     * @param integer $storeId Store id.
     *
     * @return bool
     */
    private function useStemming($storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::THESAURUS_ANALYSIS_USE_STEMMING_XML_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieves the stemming related token filters defined in the standard analysis configuration for the store.
     *
     * @param integer $storeId Store Id.
     *
     * @return array
     */
    private function getStemmingTokenFilters($storeId): array
    {
        $analysisSettings = $this->indexSettings->getAnalysisSettings($storeId);

        $tokenFilters = [];

        $possibleTokenFilters  = [
            self::STEMMER_BEFORE_FILTER,
            self::STEMMER_OVERRIDE_FILTER,
            self::STEMMER_FILTER,
            self::STEMMER_AFTER_FILTER,
        ];
        foreach ($possibleTokenFilters as $tokenFilter) {
            if (array_key_exists('filter', $analysisSettings) && array_key_exists($tokenFilter, $analysisSettings['filter'])) {
                $tokenFilters[$tokenFilter] = $analysisSettings['filter'][$tokenFilter];
            }
        }

        return $tokenFilters;
    }

    /**
     * Append the analyzer dedicated to match and detect multi-words synonyms ("A B,C" or "A-B,C")
     * to the existing settings.
     *
     * @param array $settings   Original settings.
     * @param array $preFilters Token filters to add in the analysis chain _before_ the shingles detection.
     *
     * @return array
     */
    private function addShinglesAnalyzer($settings, $preFilters)
    {
        $settings['analysis']['analyzer']['shingles'] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($preFilters)) {
            $settings['analysis']['analyzer']['shingles']['filter'] = array_merge(
                $settings['analysis']['analyzer']['shingles']['filter'],
                $preFilters
            );
        }

        $settings['analysis']['analyzer']['shingles']['filter'][] = 'analyze_shingle';

        return $settings;
    }

    /**
     * Append an analyzer for a thesaurus to existing settings.
     *
     * @param array    $settings   Original settings.
     * @param string   $type       Thesaurus type.
     * @param string[] $values     Thesaurus entries in Lucene format.
     * @param array    $preFilters Token filters to add in the analysis chain _before_ the synonym/expansion filter.
     *
     * @return array
     */
    private function addAnalyzerSettings($settings, $type, $values, $preFilters)
    {
        $settings['analysis']['analyzer'][$type] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($preFilters)) {
            $settings['analysis']['analyzer'][$type]['filter'] = array_merge(
                $settings['analysis']['analyzer'][$type]['filter'],
                $preFilters
            );
        }

        if (!empty($values)) {
            $values = $this->prepareSynonymFilterData($values);
            $settings['analysis']['filter'][$type] = ['type' => 'synonym', 'synonyms' => $values];
            $settings['analysis']['analyzer'][$type]['filter'][] = $type;
        }

        $settings['analysis']['analyzer'][$type]['filter'][] = 'type_filter';
        $settings['analysis']['analyzer'][$type]['filter'][] = 'shingle';

        return $settings;
    }

    /**
     * Prepare the thesaurus data to be saved.
     * Spaces and hyphens are replaced with "_" into multiwords expression (ex foo bar => foo_bar).
     *
     * @param string[] $rows Original thesaurus text rows.
     *
     * @return string[]
     */
    private function prepareSynonymFilterData($rows)
    {
        $rowMapper = function ($row) {
            return preg_replace('/([^\s-])[\s-]+(?=[^\s-])/u', '\1_', $row);
        };

        return array_map($rowMapper, $rows);
    }
}
