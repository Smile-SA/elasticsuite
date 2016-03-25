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

namespace Smile\ElasticSuiteThesaurus\Model\Resource\Indexer;

use Smile\ElasticSuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Thesaurus indexer resource model.
 *
 * @category Smile_ElasticSuite
 * @package  Smile_ElasticSuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Thesaurus extends \Magento\Search\Model\ResourceModel\Query
{
    /**
     * Returns the synonyms list as an array in Lucene format :
     * "football,foot"
     * "sport => football, tennis"
     *
     * Whitespace are replaced by hyphens to avoid word to be separated during analysis.
     *
     * @param integer $storeId Store id.
     *
     * @return array
     */
    public function getSynonymsByStoreId($storeId)
    {
        $synonyms = [];
        $rawData = $this->getRawSynonymData($storeId);

        foreach ($rawData as $row) {
            $leftSide    = $this->prepareWord($row['query_text']);
            $rightSide   = implode(',', $this->prepareSynonymsList($row['synonym_for']));
            $synonyms[] = sprintf("%s => %s", $leftSide, $rightSide);
        }

        return $synonyms;
    }

    /**
     * Returns all search query row  having a synonym from DB.
     *
     * @param integer $storeId Store id.
     *
     * @return array
     */
    private function getRawSynonymData($storeId)
    {
        $connection = $this->getConnection();
        $select     = $connection->select();

        $select->from($this->getMainTable())
            ->where('store_id = ?', $storeId)
            ->where('synonym_for IS NOT NULL');

        return $connection->fetchAll($select);
    }

    /**
     * Prepare a word list to be indexed.
     *
     * @param string|array $wordList List of word to be transformed.
     *
     * @return array
     */
    private function prepareSynonymsList($wordList)
    {
        if (!is_array($wordList)) {
            $wordList = explode(',', $wordList);
        }

        return array_map([$this, 'prepareWord'], $wordList);
    }

    /**
     * Preprare a single word to be indexed.
     *
     * @param string $word Word indexed.
     *
     * @return string
     */
    private function prepareWord($word)
    {
        return preg_replace('/\s+/', ThesaurusIndex::WORD_DELIMITER, trim($word));
    }
}
