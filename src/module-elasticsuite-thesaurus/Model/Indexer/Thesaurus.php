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

namespace Smile\ElasticsuiteThesaurus\Model\Indexer;

use Smile\ElasticsuiteThesaurus\Model\ResourceModel\Indexer\Thesaurus as ResourceModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Synonym indexer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Thesaurus implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var string
     */
    const INDEXER_ID = 'elasticsuite_thesaurus';

    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IndexHandler
     */
    private $indexHandler;

    /**
     * Constructor.
     *
     * @param ResourceModel         $resourceModel Synonym indexer resource model.
     * @param StoreManagerInterface $storeManager  Store manager.
     * @param IndexHandler          $indexHandler  Index handler.
     */
    public function __construct(
        ResourceModel $resourceModel,
        StoreManagerInterface $storeManager,
        IndexHandler $indexHandler
    ) {
        $this->resourceModel = $resourceModel;
        $this->storeManager  = $storeManager;
        $this->indexHandler  = $indexHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function executeFull()
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $synonyms   = $this->resourceModel->getSynonyms($storeId);
            $expansions = $this->resourceModel->getExpansions($storeId);
            $this->indexHandler->reindex($storeId, $synonyms, $expansions);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function executeList(array $ids)
    {
        throw new \Exception("Diff indexing is not supported for synonyms. Invalidate the index instead.");
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * {@inheritDoc}
     */
    public function executeRow($id)
    {
        throw new \Exception("Diff indexing is not supported for synonyms. Invalidate the index instead.");
    }

    /**
     * {@inheritDoc}
     */
    public function execute($ids)
    {
        throw new \Exception("Diff indexing is not supported for synonyms. Invalidate the index instead.");
    }
}
