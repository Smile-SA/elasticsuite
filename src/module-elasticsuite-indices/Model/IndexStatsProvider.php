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
     * @var null
     */
    private $elasticsuiteIndices = null;

    /**
     * @var null
     */
    private $indicesStats = null;

    /**
     * Constructor.
     *
     * @param ClientInterface     $client              ES client.
     * @param IndicesList         $indicesList         Index list.
     * @param IndexStatusProvider $indexStatusProvider Index Status Provider.
     */
    public function __construct(
        ClientInterface $client,
        IndicesList $indicesList,
        IndexStatusProvider $indexStatusProvider
    ) {
        $this->client = $client;
        $this->indicesList = $indicesList;
        $this->indexStatusProvider = $indexStatusProvider;
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
            'index_name'  => $indexName,
            'index_alias' => $alias,
        ];

        try {
            if (!isset($this->indicesStats[$indexName])) {
                $indexStatsResponse             = $this->client->indexStats($indexName);
                $this->indicesStats[$indexName] = current($indexStatsResponse['indices']);
            }

            $indexStats = $this->indicesStats[$indexName];

            $data['number_of_documents'] = $indexStats['total']['docs']['count'];
            $data['size']                = $this->sizeFormatted($indexStats['total']['store']['size_in_bytes']);
            $data['index_status']        = $this->indexStatusProvider->getIndexStatus($indexName, $alias);
        } catch (Exception $e) {
            $data['index_status'] = IndexStatus::REBUILDING_STATUS;
        }

        return $data;
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
     * @param string $bytes Bytes.
     *
     * @return string
     */
    private function sizeFormatted($bytes): string
    {
        if ($bytes > 0) {
            $unit = (int) log($bytes, 1024);
            $units = [__('B'), __('KB'), __('MB'), __('GB')];

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%d %s', $bytes / 1024 ** $unit, $units[$unit]);
            }
        }

        return $bytes;
    }

    /**
     * Init indices stats by calling once and for all.
     *
     * @return void
     */
    private function initStats()
    {
        if ($this->indicesStats === null) {
            $indexStatsResponse = $this->client->indexStats('_all');
            $this->indicesStats = $indexStatsResponse['indices'] ?? [];
        }
    }
}
