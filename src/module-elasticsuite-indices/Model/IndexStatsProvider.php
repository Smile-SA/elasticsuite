<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteIndices\Model;

use Exception;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;

/**
 * Class IndexStatsProvider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class IndexStatsProvider
{
    /**
     * Cache Key Prefix.
     */
    const CACHE_KEY_PREFIX = 'es_index_shard_settings';

    /**
     * Cache Tag.
     */
    const CACHE_TAG = 'index_settings';

    /**
     * @var int Cache lifetime.
     */
    const CACHE_LIFETIME = 7200;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var IndicesList
     */
    protected $indicesList;

    /**
     * @var IndexStatusProvider
     */
    protected $indexStatusProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var null
     */
    private $elasticsuiteIndices = null;

    /**
     * @var null
     */
    private $indicesStats = null;

    /**
     * @var array
     */
    private $cachedIndexSettings = null;

    /**
     * Constructor.
     *
     * @param ClientInterface     $client              ES client.
     * @param IndicesList         $indicesList         Index list.
     * @param IndexStatusProvider $indexStatusProvider Index Status Provider.
     * @param LoggerInterface     $logger              Logger.
     */
    public function __construct(
        ClientInterface $client,
        IndicesList $indicesList,
        IndexStatusProvider $indexStatusProvider,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->indicesList = $indicesList;
        $this->indexStatusProvider = $indexStatusProvider;
        $this->logger = $logger;
        $this->initStats();
    }

    /**
     * Get ElasticSuite indices.
     *
     * @param array $params Parameters array.
     * @return array
     * @throws \Exception
     */
    public function getElasticSuiteIndices($params = []): array
    {
        if ($this->elasticsuiteIndices === null) {
            $this->elasticsuiteIndices = [];

            foreach ($this->client->getIndexAliases($params) as $name => $aliases) {
                if ($this->isElasticSuiteIndex($name)) {
                    $this->elasticsuiteIndices[$name] = $aliases ? key($aliases['aliases']) : null;
                }
            }
            ksort($this->elasticsuiteIndices, SORT_STRING | SORT_NATURAL);
        }

        return $this->elasticsuiteIndices;
    }

    /**
     * Delete ElasticSuite index.
     *
     * @param string $indexName Index name.
     * @return void
     */
    public function deleteIndex($indexName): void
    {
        $this->client->deleteIndex($indexName);
    }

    /**
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     * @return array
     */
    public function indexStats($indexName, $alias): array
    {
        $data = [
            'index_name'          => $indexName,
            'index_alias'         => $alias,
            'number_of_documents' => 'N/A',
            'size'                => 'N/A',
            'number_of_shards'    => 'N/A',
            'number_of_replicas'  => 'N/A',
        ];

        try {
            // Retrieve number of shards and replicas configuration.
            $data['number_of_shards'] = $this->getPrimaryShardsConfiguration($indexName) ?? 'N/A';
            $data['number_of_replicas'] = $this->getReplicasConfiguration($indexName) ?? 'N/A';

            if (!isset($this->indicesStats[$indexName])) {
                $indexStatsResponse             = $this->client->indexStats($indexName);
                $this->indicesStats[$indexName] = current($indexStatsResponse['indices']);
            }

            $indexStats = $this->indicesStats[$indexName];

            $data['number_of_documents'] = $indexStats['total']['docs']['count'];
            $data['index_status']        = $this->indexStatusProvider->getIndexStatus($indexName, $alias);
            if (isset($indexStats['total']['store']['size_in_bytes'])) {
                $data['size'] = $this->sizeFormatted((int) $indexStats['total']['store']['size_in_bytes']);
                $data['size_in_bytes'] = $indexStats['total']['store']['size_in_bytes'];
            }
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Error when loading/parsing statistics for index "%s"', $indexName),
                ['exception' => $e]
            );
            $data['index_status'] = IndexStatus::CLOSED_STATUS;
        }

        return $data;
    }

    /**
     * Get index settings with caching.
     *
     * @param string $indexName Index name.
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getIndexSettings(string $indexName): array
    {
        return $this->cachedIndexSettings[$indexName] ?? [];
    }

    /**
     * Retrieve number of primary shards from index settings.
     *
     * @param string $indexName Index name.
     *
     * @return int|null
     */
    private function getPrimaryShardsConfiguration($indexName): ?int
    {
        // Retrieve the index settings.
        $indexSettings = $this->getIndexSettings($indexName);

        $shards = null;
        if (isset($indexSettings['settings']['index']['number_of_shards'])) {
            $shards = (int) $indexSettings['settings']['index']['number_of_shards'];
        }

        return $shards;
    }

    /**
     * Retrieve number of replicas from index settings.
     *
     * @param string $indexName Index name.
     *
     * @return int|null
     */
    private function getReplicasConfiguration($indexName): ?int
    {
        // Retrieve the index settings.
        $indexSettings = $this->getIndexSettings($indexName);

        $replicas = null;
        if (isset($indexSettings['settings']['index']['number_of_replicas'])) {
            $replicas = (int) $indexSettings['settings']['index']['number_of_replicas'];
        }

        return $replicas;
    }

    /**
     * Returns if index is elastic suite index.
     *
     * @param string $indexName Index name.
     * @return bool
     */
    private function isElasticSuiteIndex($indexName): bool
    {
        foreach ($this->indicesList->getList() as $elasticSuiteIndex) {
            if (strpos($indexName, $elasticSuiteIndex) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Size formatted.
     *
     * @param int $bytes Bytes.
     *
     * @return string
     */
    private function sizeFormatted(int $bytes): string
    {
        if ($bytes > 0) {
            $unit = (int) log($bytes, 1024);
            $units = [__('B'), __('KB'), __('MB'), __('GB')];

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / 1024 ** $unit, $units[$unit]);
            }
        }

        return 'undefined';
    }

    /**
     * Init indices stats by calling once and for all.
     *
     * @return void
     */
    private function initStats()
    {
        if ($this->indicesStats === null) {
            try {
                $indexStatsResponse = $this->client->indexStats('_all');
                $this->indicesStats = $indexStatsResponse['indices'] ?? [];
            } catch (Exception $e) {
                $this->logger->error('Error when loading all indices statistics', ['exception' => $e]);
                $this->indicesStats = [];
            }
        }

        if (null === $this->cachedIndexSettings) {
            try {
                $indicesShardSettings = $this->client->getSpecificSettings(
                    '_all',
                    ['index.number_of_shards', 'index.number_of_replicas']
                );
                $this->cachedIndexSettings = $indicesShardSettings;
            } catch (Exception $e) {
                $this->logger->error('Error when loading all indices shard settings', ['exception' => $e]);
                $this->cachedIndexSettings = [];
            }
        }
    }
}
