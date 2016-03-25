<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteThesaurus\Model\Indexer;

use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticSuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticSuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Synonym index handler.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
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
     * Constructor.
     *
     * @param ClientFactoryInterface  $clientFactory       ES Client factory.
     * @param IndexSettingsHelper     $indexSettingsHelper Index settings helper.
     * @param IndexOperationInterface $indexManager        ES index management tool
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        IndexSettingsHelper $indexSettingsHelper,
        IndexOperationInterface $indexManager
    ) {
        $this->client              = $clientFactory->createClient();
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->indexManager        = $indexManager;
    }

    /**
     * Create the synonyms index for a store id.
     *
     * @param integer $storeId  Store id.
     * @param array   $synonyms Raw synonyms list.
     *
     * @return void
     */
    public function reindex($storeId, $synonyms)
    {
        $indexIdentifier = ThesaurusIndex::INDEX_IDENTIER;
        $indexName       = $this->indexSettingsHelper->createIndexNameFromIdentifier($indexIdentifier, $storeId);
        $indexAlias      = $this->indexSettingsHelper->getIndexAliasFromIdentifier($indexIdentifier, $storeId);
        $indexSettings   = ['settings' => $this->getIndexSettings($synonyms)];

        $this->client->indices()->create(['index' => $indexName, 'body' => $indexSettings]);
        $this->indexManager->proceedIndexInstall($indexName, $indexAlias);
    }

    /**
     * Returns index settings.
     *
     * @param array $synonyms Raw synonyms list.
     *
     * @return array
     */
    private function getIndexSettings($synonyms)
    {
        $settings = [
            'number_of_shards'   => $this->indexSettingsHelper->getNumberOfShards(),
            'number_of_replicas' => $this->indexSettingsHelper->getNumberOfReplicas(),
        ];

        $settings['analysis']['filter']['shingle'] = [
            'type' => 'shingle',
            'output_unigrams' => true,
            'token_separator' => ThesaurusIndex::WORD_DELIMITER,
        ];

        $settings['analysis']['analyzer']['synonym'] = [
            'tokenizer' => 'standard',
            'filter' => ['lowercase', 'shingle'],
        ];

        if (!empty($synonyms)) {
            $settings['analysis']['filter']['synonym'] = [
                'type' => 'synonym',
                'synonyms' => $synonyms,
            ];

            $settings['analysis']['analyzer']['synonym']['filter'][] = 'synonym';
        }

        return $settings;
    }
}
