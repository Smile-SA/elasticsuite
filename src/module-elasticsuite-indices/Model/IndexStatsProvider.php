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
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
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
    const CACHE_KEY_PREFIX = 'es_index_settings_';

    /**
     * Cache Tag.
     */
    const CACHE_TAG = 'index_settings';

    /**
     * @var int Cache lifetime.
     */
    const CACHE_LIFETIME = 7200;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

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
    private $cachedIndexSettings = [];

    /**
     * Constructor.
     *
     * @param CacheHelper         $cacheHelper         ES cache helper.
     * @param ClientInterface     $client              ES client.
     * @param IndicesList         $indicesList         Index list.
     * @param IndexStatusProvider $indexStatusProvider Index Status Provider.
     * @param LoggerInterface     $logger              Logger.
     */
    public function __construct(
        CacheHelper  $cacheHelper,
        ClientInterface $client,
        IndicesList $indicesList,
        IndexStatusProvider $indexStatusProvider,
        LoggerInterface $logger
    ) {
        $this->cacheHelper = $cacheHelper;
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
            $data['number_of_shards'] = $this->getShardsConfiguration($indexName);
            $data['number_of_replicas'] = $this->getReplicasConfiguration($indexName);

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
    public function getIndexSettings(string $indexName): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $indexName;

        // Check if the settings are already in memory.
        if (!isset($this->cachedIndexSettings[$cacheKey])) {
            $cachedData = $this->cacheHelper->loadCache($cacheKey);

            if ($cachedData) {
                $this->cachedIndexSettings[$cacheKey] = $cachedData;
            } else {
                $settingsData = $this->client->getSettings($indexName);

                // Save to cache with a tag.
                $this->cacheHelper->saveCache(
                    $cacheKey,
                    $settingsData,
                    $this->getCacheTags(),
                    self::CACHE_LIFETIME
                );

                $this->cachedIndexSettings[$cacheKey] = $settingsData;
            }
        }

        return $this->cachedIndexSettings[$cacheKey];
    }

    /**
     * Retrieve number of shards from index settings.
     *
     * @param string $indexName Index name.
     *
     * @return int
     */
    public function getShardsConfiguration($indexName)
    {
        // Retrieve the index settings.
        $indexSettings = $this->getIndexSettings($indexName);

        // Check if settings for the given index exist and retrieve number_of_shards.
        if (isset($indexSettings[$indexName]['settings']['index']['number_of_shards'])) {
            return (int) $indexSettings[$indexName]['settings']['index']['number_of_shards'];
        }

        // Return null or throw an exception if the value doesn't exist.
        throw new \RuntimeException("number_of_shards setting not found for index: $indexName");
    }

    /**
     * Retrieve number of replicas from index settings.
     *
     * @param string $indexName Index name.
     *
     * @return int
     */
    public function getReplicasConfiguration($indexName)
    {
        // Retrieve the index settings.
        $indexSettings = $this->getIndexSettings($indexName);

        // Check if settings for the given index exist and retrieve number_of_replicas.
        if (isset($indexSettings[$indexName]['settings']['index']['number_of_replicas'])) {
            return (int) $indexSettings[$indexName]['settings']['index']['number_of_replicas'];
        }

        // Return null or throw an exception if the value doesn't exist.
        throw new \RuntimeException("number_of_replicas setting not found for index: $indexName");
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
    }

    /**
     * Get cache tags.
     *
     * @return array
     */
    private function getCacheTags()
    {
        return [
            \Smile\ElasticsuiteCore\Cache\Type\Elasticsuite::CACHE_TAG,
            self::CACHE_TAG,
        ];
    }
}
