<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\ResourceModel\Search;

use Magento\Search\Model\Query as QueryModel;

/**
 * Custom search request resource model.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Query extends \Magento\Search\Model\ResourceModel\Query
{
    /**
     * Save query with number of results and is spellchecked.
     *
     * @param QueryModel $query Search query object.
     *
     * @return void
     */
    public function saveSearchResults(QueryModel $query)
    {
        $adapter = $this->getConnection();
        $table = $this->getMainTable();

        $numResults     = $query->getNumResults();
        $isSpellchecked = (int) $query->getIsSpellchecked();

        $saveData = [
            'store_id'        => $query->getStoreId(),
            'query_text'      => $query->getQueryText(),
            'num_results'     => $numResults,
            'is_spellchecked' => $isSpellchecked,
        ];

        $updateData = ['num_results' => $numResults, 'is_spellchecked' => $isSpellchecked];

        $adapter->insertOnDuplicate($table, $saveData, $updateData);
    }
}
