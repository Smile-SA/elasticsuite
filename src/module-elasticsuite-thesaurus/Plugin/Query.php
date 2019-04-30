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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Plugin;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Search\Model\Query as QueryModel;
use Smile\ElasticsuiteThesaurus\Model\Indexer\Thesaurus as ThesaurusIndexer;

/**
 * Thesaurus search query model plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Query
{
    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * Constructor.
     *
     * @param IndexerRegistry $indexerRegistry Indexers registry.
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }


    /**
     * Avoid original query rewrite using synonym_for to be applied into the query factory.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param QueryModel $subject Query Model.
     * @param \Closure   $proceed Original function.
     * @param string     $text    Query text.
     *
     * @return \Magento\Search\Model\Query
     */
    public function aroundLoadByQuery(QueryModel $subject, \Closure $proceed, $text)
    {
        return $subject->loadByQueryText($text);
    }

    /**
     * Ensure the index is invalidated when synonyms are updated.
     *
     * @param QueryModel $subject Saved query.
     * @param \Closure   $proceed Original save method.
     *
     * @return QueryModel
     */
    public function aroundSave(QueryModel $subject, \Closure $proceed)
    {
        $needReindex = $subject->dataHasChangedFor('synonym_for');

        $result = $proceed();

        if ($needReindex) {
            $this->indexerRegistry->get(ThesaurusIndexer::INDEXER_ID)->invalidate();
        }

        return $result;
    }
}
