<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Model;

use Exception;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;

/**
 * Service class responsible for purging ghost indices.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class GhostIndexPurger
{
    /**
     * @var IndexStatsProvider
     */
    private IndexStatsProvider $indexStatsProvider;

    /**
     * @var IndexStatusProvider
     */
    private IndexStatusProvider $indexStatusProvider;

    /**
     * Constructor.
     *
     * @param IndexStatsProvider  $indexStatsProvider  Index stats provider.
     * @param IndexStatusProvider $indexStatusProvider Index status provider.
     */
    public function __construct(
        IndexStatsProvider $indexStatsProvider,
        IndexStatusProvider $indexStatusProvider
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
        $this->indexStatusProvider = $indexStatusProvider;
    }

    /**
     * Purge all ghost indices.
     *
     * Iterates through all known ElasticSuite indices and deletes those determined
     * to be in "ghost" status (i.e., no longer attached to any alias and considered safe to delete).
     *
     * @return int
     * @throws Exception
     */
    public function purge(): int
    {
        $deleted = [];

        $indices = $this->indexStatsProvider->getElasticSuiteIndices();

        foreach ($indices as $indexName => $alias) {
            if ($this->indexCanBeRemoved($indexName, $alias)) {
                try {
                    $this->indexStatsProvider->deleteIndex($indexName);
                    $deleted[] = $indexName;
                } catch (Exception $e) {
                    // Optional: Log the exception if needed.
                }
            }
        }

        return count($deleted);
    }

    /**
     * Determines if the index can be safely removed (is ghost).
     *
     * @param string      $indexName Index name.
     * @param string|null $alias     Index alias.
     *
     * @return bool
     */
    private function indexCanBeRemoved(string $indexName, ?string $alias): bool
    {
        return $this->indexStatusProvider->getIndexStatus($indexName, $alias) === IndexStatus::GHOST_STATUS;
    }
}
