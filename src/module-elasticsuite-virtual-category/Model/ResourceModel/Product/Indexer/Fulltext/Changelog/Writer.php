<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Product\Indexer\Fulltext\Changelog;

use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class Writer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 */
class Writer
{
    /**
     * @var  \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * Writer constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry Indexer registry
     * @param \Magento\Framework\App\ResourceConnection  $resource        Resource
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->resource = $resource;
    }

    /**
     * Add products to the fulltext indexer changelog table
     *
     * @param array $productIds IDs of products to schedule for reindexing
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function addProducts($productIds)
    {
        $fullTextIndexer    = $this->indexerRegistry->get(\Magento\CatalogSearch\Model\Indexer\Fulltext::INDEXER_ID);
        $indexerChangeLog   = $fullTextIndexer->getView()->getChangelog();

        $changelogTableName = $this->resource->getTableName($indexerChangeLog->getName());
        $columnName         = $indexerChangeLog->getColumnName();

        try {
            $indexerChangeLog->create();

            $this->resource->getConnection()->insertArray(
                $changelogTableName,
                [$columnName],
                $productIds
            );
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__(
                'Could not schedule products for reindexing : %1',
                $e->getMessage()
            ));
        }
    }
}
