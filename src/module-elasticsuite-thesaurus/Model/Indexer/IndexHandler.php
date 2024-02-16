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
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
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
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * Constructor.
     *
     * @param ClientInterface         $client              ES client.
     * @param IndexOperationInterface $indexManager        ES index management tool
     * @param IndexSettingsHelper     $indexSettingsHelper Index settings helper.
     * @param CacheHelper             $cacheHelper         ES caching helper.
     */
    public function __construct(
        ClientInterface $client,
        IndexOperationInterface $indexManager,
        IndexSettingsHelper $indexSettingsHelper,
        CacheHelper $cacheHelper
    ) {
        $this->client              = $client;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexManager        = $indexManager;
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
        $indexSettings   = ['settings' => $this->getIndexSettings($synonyms, $expansions)];
        $this->client->createIndex($indexName, $indexSettings);
        $this->indexManager->proceedIndexInstall($indexName, $indexAlias);
        $this->cacheHelper->cleanIndexCache(ThesaurusIndex::INDEX_IDENTIER, $storeId);
    }

    /**
     * Returns index settings.
     *
     * @param string[] $synonyms   Raw synonyms list.
     * @param string[] $expansions Raw expansions list.
     *
     * @return array
     */
    private function getIndexSettings($synonyms, $expansions)
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

        $settings['analysis']['analyzer']['shingles'] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding', 'analyze_shingle'],
        ];

        $settings = $this->addAnalyzerSettings($settings, 'synonym', $synonyms);
        $settings = $this->addAnalyzerSettings($settings, 'expansion', $expansions);

        return $settings;
    }

    /**
     * Append an analyzer for a thesaurus to existing settings.
     *
     * @param array    $settings Original settings.
     * @param string   $type     Thesaurus type.
     * @param string[] $values   Thesaurus entries in Lucene format.
     *
     * @return array
     */
    private function addAnalyzerSettings($settings, $type, $values)
    {
        $settings['analysis']['analyzer'][$type] = [
            'tokenizer' => 'whitespace',
            'filter' => ['lowercase', 'asciifolding'],
        ];

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
        $rowMaper = function ($row) {
            return preg_replace('/([^\s-])[\s-]+(?=[^\s-])/u', '\1_', $row);
        };

        return array_map($rowMaper, $rows);
    }
}
