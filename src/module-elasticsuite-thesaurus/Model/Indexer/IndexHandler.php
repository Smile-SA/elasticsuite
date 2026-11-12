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

use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusStemmingConfig;
use Smile\ElasticsuiteCore\Helper\Text as TextHelper;
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
     * Max number of words sent in a single _analyze call when stemming the thesaurus rows
     * (stays well below the default index.analyze.max_token_count of 10000).
     *
     * @var integer
     */
    const ANALYZE_MAX_WORDS = 5000;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $analyzeIndexIdentifier;

    /**
     * Constructor.
     *
     * @param ClientInterface         $client                 ES client.
     * @param IndexOperationInterface $indexManager           ES index management tool
     * @param IndexSettingsHelper     $indexSettingsHelper    Index settings helper.
     * @param IndexSettingsInterface  $indexSettings          Index settings provider.
     * @param ThesaurusStemmingConfig $stemmingConfig         Stemming configuration.
     * @param CacheHelper             $cacheHelper            ES caching helper.
     * @param TextHelper              $textHelper             Text manipulation helper.
     * @param LoggerInterface         $logger                 Logger.
     * @param string|null             $analyzeIndexIdentifier Identifier of the index used to stem the thesaurus rows
     *                                                        (null: cluster-level _analyze).
     */
    public function __construct(
        ClientInterface $client,
        IndexOperationInterface $indexManager,
        IndexSettingsHelper $indexSettingsHelper,
        IndexSettingsInterface $indexSettings,
        ThesaurusStemmingConfig $stemmingConfig,
        CacheHelper $cacheHelper,
        TextHelper $textHelper,
        LoggerInterface $logger,
        $analyzeIndexIdentifier = null
    ) {
        $this->client              = $client;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexManager        = $indexManager;
        $this->indexSettings       = $indexSettings;
        $this->stemmingConfig      = $stemmingConfig;
        $this->cacheHelper         = $cacheHelper;
        $this->textHelper          = $textHelper;
        $this->logger              = $logger;
        $this->analyzeIndexIdentifier = $analyzeIndexIdentifier;
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

        // Some clusters refuse the cluster-level _analyze (no indices:admin/analyze permission on the cluster).
        // An index can then be set through the analyzeIndexIdentifier constructor argument (di.xml).
        $analyzeIndex = null;
        if (!empty($stemmingFilters) && $this->analyzeIndexIdentifier !== null) {
            $analyzeIndex = $this->indexSettingsHelper->getIndexAliasFromIdentifier($this->analyzeIndexIdentifier, $storeId);
        }

        $settings = $this->addAnalyzerSettings($settings, 'synonym', $synonyms, $stemmingFilters, $analyzeIndex);
        $settings = $this->addAnalyzerSettings($settings, 'expansion', $expansions, $stemmingFilters, $analyzeIndex);

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
     * @param array       $settings        Original settings.
     * @param string      $type            Thesaurus type.
     * @param string[]    $values          Thesaurus entries in Lucene format.
     * @param array       $stemmingFilters Stemming token filters applied to the thesaurus entries.
     * @param string|null $analyzeIndex    Index used to stem the entries (null: cluster-level _analyze).
     *
     * @return array
     */
    private function addAnalyzerSettings($settings, $type, $values, $stemmingFilters, $analyzeIndex = null)
    {
        $settings['analysis']['analyzer'][$type] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

        if (!empty($values)) {
            $values = $this->prepareSynonymFilterData($values, $stemmingFilters, $analyzeIndex);
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
     * @param string[]    $rows            Original thesaurus text rows.
     * @param array       $stemmingFilters Stemming token filters to apply before synonym processing.
     * @param string|null $analyzeIndex    Index used to stem the rows (null: cluster-level _analyze).
     *
     * @return string[]
     */
    private function prepareSynonymFilterData($rows, $stemmingFilters, $analyzeIndex = null)
    {
        $rows = array_values($rows);

        if (!empty($rows) && !empty($stemmingFilters)) {
            $maskedRows  = array_map(function ($row) {
                return $this->maskRow($row);
            }, $rows);
            $stemmedRows = [];
            foreach ($this->getAnalyzeBatches($maskedRows) as [$offset, $length]) {
                $stemmedRows[] = $this->extractStemsFromThesaurusRows(
                    array_slice($rows, $offset, $length),
                    array_slice($maskedRows, $offset, $length),
                    $stemmingFilters,
                    $analyzeIndex
                );
            }
            $rows = array_merge(...$stemmedRows);
        }

        return array_map(function ($row) {
            return preg_replace('/([^\s-])[\s-]+(?=[^\s-])/u', '\1_', $row);
        }, $rows);
    }

    /**
     * Split the masked thesaurus rows into batches of at most ANALYZE_MAX_WORDS whitespace-separated words.
     *
     * @param string[] $maskedRows Masked thesaurus rows (list).
     *
     * @return array List of [offset, length] of each batch in the rows.
     */
    private function getAnalyzeBatches($maskedRows)
    {
        $batches   = [];
        $start     = 0;
        $wordCount = 0;

        foreach ($maskedRows as $index => $maskedRow) {
            $rowWordCount = count(preg_split('/\s+/u', $maskedRow, -1, PREG_SPLIT_NO_EMPTY));
            if ($index > $start && $wordCount + $rowWordCount > self::ANALYZE_MAX_WORDS) {
                $batches[] = [$start, $index - $start];
                $start     = $index;
                $wordCount = 0;
            }
            $wordCount += $rowWordCount;
        }
        $batches[] = [$start, count($maskedRows) - $start];

        return $batches;
    }

    /**
     * Mask the thesaurus syntax ("=>", ",") and the hyphens with spaces, so that the whitespace tokenizer
     * splits every word, the same way as the query, and the offsets still point into the original row.
     *
     * @param string $row Thesaurus row.
     *
     * @return string
     */
    private function maskRow($row)
    {
        return strtr($row, ['=>' => '  ', ',' => ' ', '-' => ' ']);
    }

    /**
     * Stem a batch of thesaurus rows with a single _analyze call.
     * The rows are joined with line breaks, then each token is replaced in its own row.
     * The analysis offsets are UTF-16 code units: they are converted to characters before the replacement.
     * If the analysis fails, the rows are returned unstemmed so that the thesaurus index can still be built.
     *
     * @param string[]    $rows            Thesaurus rows.
     * @param string[]    $maskedRows      The same rows, masked.
     * @param array       $stemmingFilters Stemming token filters configuration.
     * @param string|null $analyzeIndex    Index used for the _analyze call (null: cluster-level _analyze).
     *
     * @return string[] Rows with stemmed terms.
     */
    private function extractStemsFromThesaurusRows($rows, $maskedRows, $stemmingFilters, $analyzeIndex)
    {
        $rowStarts = [];
        $cursor    = 0;
        foreach ($rows as $index => $row) {
            $rowStarts[$index] = $cursor;
            $cursor           += $this->getUtf16Length($row) + 1;
        }

        $analyzeParams = [
            'body' => [
                'tokenizer'   => 'whitespace',
                'filter'      => array_merge(['lowercase', 'asciifolding'], array_values($stemmingFilters)),
                'char_filter' => [],
                'text'        => implode("\n", $maskedRows),
            ],
        ];

        if ($analyzeIndex !== null) {
            $analyzeParams['index'] = $analyzeIndex;
        }

        try {
            $response = $this->client->analyze($analyzeParams);
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf('Thesaurus rules indexed without stemming, the _analyze call failed: %s', $e->getMessage())
            );

            return $rows;
        }

        // Replace the terms from the end of each row, so that the offsets of the remaining terms stay valid.
        foreach ($this->getTokensByRow($response['tokens'] ?? [], $rowStarts) as $index => $tokens) {
            krsort($tokens);
            foreach ($tokens as $startOffset => $token) {
                $endOffset    = $startOffset + $token['end_offset'] - $token['start_offset'];
                $start        = $this->getCharOffset($rows[$index], $startOffset);
                $length       = $this->getCharOffset($rows[$index], $endOffset) - $start;
                $rows[$index] = $this->textHelper->mbSubstrReplace($rows[$index], $token['token'], $start, $length);
            }
        }

        return $rows;
    }

    /**
     * Dispatch the tokens of a batch to their row, keyed by their offset in the row.
     * If several tokens share a position, the last one is kept (keyword_repeat emits the word, then its stem).
     *
     * @param array $tokens    Analysis tokens of the batch.
     * @param int[] $rowStarts Offset of each row in the batch text.
     *
     * @return array
     */
    private function getTokensByRow($tokens, $rowStarts)
    {
        $tokensByRow = [];
        $rowIndex    = 0;
        $lastIndex   = count($rowStarts) - 1;

        foreach ($tokens as $token) {
            while ($rowIndex < $lastIndex && $token['start_offset'] >= $rowStarts[$rowIndex + 1]) {
                $rowIndex++;
            }
            $startOffset = $token['start_offset'] - $rowStarts[$rowIndex];
            $tokensByRow[$rowIndex][$startOffset] = $token;
        }

        return $tokensByRow;
    }

    /**
     * Length of a text in UTF-16 code units, the unit of the analysis offsets.
     *
     * @param string $text Text.
     *
     * @return int
     */
    private function getUtf16Length($text)
    {
        return (int) (strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) / 2);
    }

    /**
     * Convert an offset in UTF-16 code units into an offset in characters.
     * Both are the same unless the text has characters outside the BMP (emoji).
     *
     * @param string $text        Text.
     * @param int    $utf16Offset Offset in UTF-16 code units.
     *
     * @return int
     */
    private function getCharOffset($text, $utf16Offset)
    {
        if ($this->getUtf16Length($text) === mb_strlen($text)) {
            return $utf16Offset;
        }

        $utf16Text = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');

        return mb_strlen(mb_convert_encoding(substr($utf16Text, 0, 2 * $utf16Offset), 'UTF-8', 'UTF-16LE'));
    }
}
