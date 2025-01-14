<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Exception;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;

/**
 * Class GhostIndicesCheck.
 *
 * Health check to identify any ghost indices in the Elasticsearch cluster.
 */
class GhostIndicesCheck implements CheckInterface
{
    /**
     * Route to Elasticsuite -> Indices page.
     */
    private const ROUTE_ELASTICSUITE_INDICES = 'smile_elasticsuite_indices';

    public const GHOST_STATUS = 'ghost';

    /**
     * @var IndexStatsProvider
     */
    private $indexStatsProvider;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param IndexStatsProvider $indexStatsProvider Index stats provider.
     * @param UrlInterface       $urlBuilder         URL builder.
     */
    public function __construct(
        IndexStatsProvider $indexStatsProvider,
        UrlInterface $urlBuilder
    ) {
        $this->indexStatsProvider = $indexStatsProvider;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Retrieve the unique identifier for this health check.
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'ghost_indices_check';
    }

    /**
     * Retrieve a brief description of this health check.
     *
     * @return string
     * @throws Exception
     */
    public function getDescription(): string
    {
        $ghostCount = $this->getNumberOfGhostIndices();

        if ($ghostCount > 0) {
            // Description when ghost indices are found.
            // @codingStandardsIgnoreStart
            return implode(
                '<br />',
                [
                    __(
                        'You have <strong>%1 ghost indices</strong>. Ghost indices have a footprint on your Elasticsearch cluster health. '
                        . 'You should consider removing them.',
                        $ghostCount
                    ),
                    __(
                        'Click <a href="%1"><strong>here</strong></a> to go to the <strong>Elasticsuite Indices</strong> page to take appropriate actions.',
                        $this->getElasticsuiteIndicesUrl()
                    )
                ]
            );
            // @codingStandardsIgnoreEnd
        }

        // Description when no ghost indices are found.
        return __('There are no ghost indices in your Elasticsearch cluster. No action is required at this time.');
    }

    /**
     * Retrieve the status of this health check.
     * Returns 'warning' if ghost indices are found, otherwise 'success'.
     *
     * @return string
     * @throws Exception
     */
    public function getStatus(): string
    {
        return $this->hasGhostIndices() ? 'warning' : 'success';
    }

    /**
     * Retrieve the sort order for this health check.
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return 10; // Adjust as necessary.
    }

    /**
     * Checks if there are any ghost indices.
     *
     * @return bool
     * @throws Exception
     */
    private function hasGhostIndices(): bool
    {
        return $this->getNumberOfGhostIndices() > 0;
    }

    /**
     * Get number of ghost Elasticsuite indices.
     *
     * @return int
     * @throws Exception
     */
    private function getNumberOfGhostIndices(): int
    {
        $ghostIndices = 0;
        $elasticsuiteIndices = $this->indexStatsProvider->getElasticSuiteIndices();

        if ($elasticsuiteIndices !== null) {
            foreach ($elasticsuiteIndices as $indexName => $indexAlias) {
                $indexData = $this->indexStatsProvider->indexStats($indexName, $indexAlias);

                if (array_key_exists('index_status', $indexData)
                    && $indexData['index_status'] === self::GHOST_STATUS) {
                    $ghostIndices++;
                }
            }
        }

        return $ghostIndices;
    }

    /**
     * Retrieve a URL to the Elasticsuite Indices page for more information.
     *
     * @return string
     */
    private function getElasticsuiteIndicesUrl(): string
    {
        return $this->urlBuilder->getUrl(self::ROUTE_ELASTICSUITE_INDICES);
    }
}
