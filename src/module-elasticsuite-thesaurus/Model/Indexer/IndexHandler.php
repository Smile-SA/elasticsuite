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

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusStemmingConfig;
use Smile\ElasticsuiteThesaurus\Helper\Text as TextHelper;
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
     * @var ThesaurusStemmingConfig
     */
    private $stemmingConfig;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var TextHelper
     */
    private $textHelper;

    /**
     * Constructor.
     *
     * @param ClientInterface         $client              ES client.
     * @param IndexOperationInterface $indexManager        ES index management tool
     * @param IndexSettingsHelper     $indexSettingsHelper Index settings helper.
     * @param IndexSettingsInterface  $indexSettings       Index settings provider.
     * @param ThesaurusStemmingConfig $stemmingConfig      Stemming configuration.
     * @param CacheHelper             $cacheHelper         ES caching helper.
     * @param TextHelper              $textHelper          Text manipulation helper.
     */
    public function __construct(
        ClientInterface $client,
        IndexOperationInterface $indexManager,
        IndexSettingsHelper $indexSettingsHelper,
        IndexSettingsInterface $indexSettings,
        ThesaurusStemmingConfig $stemmingConfig,
        CacheHelper $cacheHelper,
        TextHelper $textHelper
    ) {
        $this->client              = $client;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexManager        = $indexManager;
        $this->indexSettings       = $indexSettings;
        $this->stemmingConfig      = $stemmingConfig;
        $this->cacheHelper         = $cacheHelper;
        $this->textHelper          = $textHelper;
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
        if ($this->stemmingConfig->useStemming($storeId)) {
            $stemmingFilters = $this->getStemmingTokenFilters($storeId);
            if (!empty($stemmingFilters)) {
                $settings['analysis']['filter'] = array_merge($settings['analysis']['filter'], $stemmingFilters);
            }
        }

        $settings = $this->addCleanAnalyzer($settings, array_keys($stemmingFilters));
        $settings = $this->addShinglesAnalyzer($settings, array_keys($stemmingFilters));

        $settings = $this->addAnalyzerSettings($settings, 'synonym', $synonyms, $stemmingFilters);
        $settings = $this->addAnalyzerSettings($settings, 'expansion', $expansions, $stemmingFilters);

        return $settings;
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
     * Append the analyzer dedicated to match clean token to the existing settings.
     *
     * @param array $settings        Original settings.
     * @param array $stemmingFilters Stemming token filters to add in the analysis chain.
     *
     * @return array
     */
    private function addCleanAnalyzer($settings, $stemmingFilters)
    {
        $settings['analysis']['analyzer']['clean'] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($stemmingFilters)) {
            $settings['analysis']['analyzer']['clean']['filter'] = array_merge(
                $settings['analysis']['analyzer']['clean']['filter'],
                $stemmingFilters
            );
        }

        return $settings;
    }

    /**
     * Append the analyzer dedicated to match and detect multi-words synonyms ("A B,C" or "A-B,C")
     * to the existing settings.
     *
     * @param array $settings        Original settings.
     * @param array $stemmingFilters Stemming token filters to add in the analysis chain _before_ the shingles detection.
     *
     * @return array
     */
    private function addShinglesAnalyzer($settings, $stemmingFilters)
    {
        $settings['analysis']['analyzer']['shingles'] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($stemmingFilters)) {
            $settings['analysis']['analyzer']['shingles']['filter'] = array_merge(
                $settings['analysis']['analyzer']['shingles']['filter'],
                $stemmingFilters
            );
        }

        $settings['analysis']['analyzer']['shingles']['filter'][] = 'analyze_shingle';

        return $settings;
    }

    /**
     * Append an analyzer for a thesaurus to existing settings.
     *
     * @param array    $settings        Original settings.
     * @param string   $type            Thesaurus type.
     * @param string[] $values          Thesaurus entries in Lucene format.
     * @param array    $stemmingFilters Stemming token filters to add in the analysis chain _before_ the synonym/expansion filter.
     *
     * @return array
     */
    private function addAnalyzerSettings($settings, $type, $values, $stemmingFilters)
    {
        $settings['analysis']['analyzer'][$type] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($values)) {
            $values = $this->prepareSynonymFilterData($values, $stemmingFilters);
            $settings['analysis']['filter'][$type] = ['type' => 'synonym', 'synonyms' => $values, 'lenient' => true];
            $settings['analysis']['analyzer'][$type]['filter'][] = $type;
        }

        $settings['analysis']['analyzer'][$type]['filter'][] = 'type_filter';
        $settings['analysis']['analyzer'][$type]['filter'][] = 'shingle';

        return $settings;
    }

    /**
     * Prepare the thesaurus data to be saved.
     * Spaces and hyphens are replaced with "_" into multiwords expression (ex foo bar => foo_bar).
     * Applies stemming filters if provided.
     *
     * @param string[] $rows            Original thesaurus text rows.
     * @param array    $stemmingFilters Stemming token filters to apply before synonym processing.
     *
     * @return string[]
     */
    private function prepareSynonymFilterData($rows, $stemmingFilters)
    {
        if (empty($rows)) {
            return $rows;
        }

        return array_map(function ($row) use ($stemmingFilters) {
            if (!empty($stemmingFilters)) {
                $row = $this->extractStemsFromThesaurusRow($row, $stemmingFilters);
            }

            return preg_replace('/([^\s-])[\s-]+(?=[^\s-])/u', '\1_', $row);
        }, $rows);
    }

    /**
     * Analyze an entire thesaurus row using Elasticsearch analyze API to extract stems.
     *
     * @param string $row             Row to analyze.
     * @param array  $stemmingFilters Stemming token filters configuration.
     *
     * @return string Analyzed row with stemmed terms.
     */
    private function extractStemsFromThesaurusRow($row, $stemmingFilters)
    {
        // Build an analysis query with stemming filters.
        $analyzeParams = [
            'body' => [
                'tokenizer' => 'whitespace',
                'filter' => array_merge(['lowercase', 'asciifolding'], array_values($stemmingFilters)),
                'char_filter' => [],
                'text' => str_replace(',', ' ', $row),
            ],
        ];

        $response = $this->client->analyze($analyzeParams);

        // Replace original terms with their stemmed counterparts in the thesaurus row.
        if (isset($response['tokens']) && !empty($response['tokens'])) {
            $rewrittenRow = $row;
            $offset = 0;

            foreach ($response['tokens'] as $token) {
                $startOffset = $token['start_offset'];
                $length = $token['end_offset'] - $token['start_offset'];

                $rewrittenRow = $this->textHelper->mbSubstrReplace(
                    $rewrittenRow,
                    $token['token'],
                    $startOffset + $offset,
                    $length
                );

                $offset += mb_strlen($token['token']) - $length;
            }

            return $rewrittenRow;
        }

        return $row;
    }
}
