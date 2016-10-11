<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Model\Indexer;

use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Synonym index handler.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexHandler
{
    /**
     * @var \Elasticsearch\Client
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
     * @param ClientFactoryInterface  $clientFactory       ES Client factory.
     * @param IndexOperationInterface $indexManager        ES index management tool
     * @param IndexSettingsHelper     $indexSettingsHelper Index settings helper.
     * @param CacheHelper             $cacheHelper         ES caching helper.
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        IndexOperationInterface $indexManager,
        IndexSettingsHelper $indexSettingsHelper,
        CacheHelper $cacheHelper
    ) {
        $this->client              = $clientFactory->createClient();
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

        $this->client->indices()->create(['index' => $indexName, 'body' => $indexSettings]);
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
        $settings = [
            'number_of_shards'      => $this->indexSettingsHelper->getNumberOfShards(),
            'number_of_replicas'    => $this->indexSettingsHelper->getNumberOfReplicas(),
            'requests.cache.enable' => true,
        ];

        $settings['analysis']['filter']['shingle'] = [
            'type' => 'shingle',
            'output_false' => true,
            'token_separator' => ThesaurusIndex::WORD_DELIMITER,
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
            'tokenizer' => 'standard',
            'filter' => ['lowercase', 'shingle'],
        ];

        if (!empty($values)) {
            $values = $this->prepareSynonymFilterData($values);
            $settings['analysis']['filter'][$type] = ['type' => 'synonym', 'synonyms' => $values];
            $settings['analysis']['analyzer'][$type]['filter'][] = $type;
        }

        return $settings;
    }

    /**
     * Prepare the thesaurus data to be saved.
     * Spaces are replaced with "_" into multiwords expression (ex foo bar => foo_bar).
     *
     * @param string[] $rows Original thesaurus text rows.
     *
     * @return string[]
     */
    private function prepareSynonymFilterData($rows)
    {
        $rowMaper = function ($row) {
            return preg_replace('/([\w])\s(?=[\w])/', '\1-', $row);
        };

        return array_map($rowMaper, $rows);
    }
}
