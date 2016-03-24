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

namespace Smile\ElasticSuiteThesaurus\Model;

use Smile\ElasticSuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface;

/**
 * Thesaurus index.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteThesaurus
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Index
{
    /**
     * @var string
     */
    const INDEX_IDENTIER = 'synonyms';

    /**
     * @var string
     */
    const WORD_DELIMITER = '-';

    /**
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * Constructor.
     *
     * @param ClientFactoryInterface $clientFactory       ES Client Factory.
     * @param IndexSettingsHelper    $indexSettingsHelper Index Settings Helper.
     */
    public function __construct(ClientFactoryInterface $clientFactory, IndexSettingsHelper $indexSettingsHelper)
    {
        $this->client              = $clientFactory->createClient();
        $this->indexSettingsHelper = $indexSettingsHelper;
    }

    /**
     * Generates all possible synonym rewrites for a store and text query.
     *
     * @param integer $storeId   Store id.
     * @param string  $queryText Text query.
     *
     * @return string[]
     */
    public function getSynonymRewrites($storeId, $queryText)
    {
        $indexName = $this->indexSettingsHelper->getIndexAliasFromIdentifier(self::INDEX_IDENTIER, $storeId);

        $analysis = $this->client->indices()->analyze(
            ['index' => $indexName, 'text' => $queryText, 'analyzer' => 'synonym']
        );

        $synonymByPositions = [];

        foreach ($analysis['tokens'] as $token) {
            if ($token['type'] == 'SYNONYM') {
                $positionKey = sprintf('%s_%s', $token['start_offset'], $token['end_offset']);
                $synonymByPositions[$positionKey][] = $token;
            }
        }

        return $this->combineSynonyms($queryText, $synonymByPositions);
    }

    /**
     * Combine analysis result to provides all possible synonyms substitution comination.
     *
     * @param string  $queryText          Original query text
     * @param unknown $synonymByPositions Synonyms array by positions.
     * @param number  $offset             Offset of previous substitutions.
     *
     * @return string[]
     */
    private function combineSynonyms($queryText, $synonymByPositions, $offset = 0)
    {
        $combinations = [];

        if (!empty($synonymByPositions)) {
            $currentPositionSynonyms = current($synonymByPositions);
            $remainingSynonyms = array_slice($synonymByPositions, 1);

            foreach ($currentPositionSynonyms as $synonym) {
                $startOffset = $synonym['start_offset'] + $offset;
                $length      = $synonym['end_offset'] - $synonym['start_offset'];
                $rewrittenQueryText = substr_replace($queryText, $synonym['token'], $startOffset, $length);
                $newOffset = strlen($rewrittenQueryText) - strlen($queryText);
                $combinations[] = $rewrittenQueryText;

                if (!empty($remainingSynonyms)) {
                    $combinations = array_merge(
                        $combinations,
                        $this->combineSynonyms($rewrittenQueryText, $remainingSynonyms, $newOffset)
                    );
                }
            }

            if (!empty($remainingSynonyms)) {
                $combinations = array_merge(
                    $combinations,
                    $this->combineSynonyms($queryText, $remainingSynonyms, $offset)
                );
            }
        }

        return $combinations;
    }
}
