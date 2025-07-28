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

use DateTime;
use Exception;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\ResourceModel\StoreIndices\CollectionFactory as StoreIndicesCollectionFactory;
use Smile\ElasticsuiteIndices\Model\ResourceModel\WorkingIndexer\CollectionFactory as WorkingIndexerCollectionFactory;

/**
 * Class IndexStatusProvider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class IndexStatusProvider
{
    /**
     * How many days after an index is a ghost.
     */
    private const NUMBER_DAYS_AFTER_INDEX_IS_GHOST = 2;

    /**
     * Store number of seconds in a day.
     */
    private const SECONDS_IN_DAY = 86400;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var DataObject
     */
    protected $storeIndices;

    /**
     * @var array
     */
    protected $workingIndexers;

    /**
     * @var array|null
     */
    private $indicesStats = null;

    /**
     * Constructor.
     *
     * @param ClientInterface                 $client                   ES client.
     * @param IndexSettingsInterface          $indexSettings            Index settings.
     * @param IndexSettingsHelper             $indexSettingsHelper      Index settings helper.
     * @param StoreIndicesCollectionFactory   $storeIndicesFactory      Store indices collection.
     * @param WorkingIndexerCollectionFactory $indexerCollectionFactory Working indexers collection.
     * @param LoggerInterface                 $logger                   Logger.
     */
    public function __construct(
        ClientInterface $client,
        IndexSettingsInterface $indexSettings,
        IndexSettingsHelper $indexSettingsHelper,
        StoreIndicesCollectionFactory $storeIndicesFactory,
        WorkingIndexerCollectionFactory $indexerCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->indexSettings = $indexSettings;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->storeIndices = $storeIndicesFactory->create()->getItems();
        $this->workingIndexers = $indexerCollectionFactory->create()->getItems();
        $this->logger = $logger;
        $this->initStats();
    }

    /**
     * Get an index status.
     *
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     *
     * @return string
     */
    public function getIndexStatus($indexName, $alias): string
    {
        $indexData = $this->indexSettingsHelper->parseIndexName($indexName);
        $indexDate = $indexData ? $indexData['datetime'] : false;

        if ($this->isExternal($indexName)) {
            return IndexStatus::EXTERNAL_STATUS;
        }

        if ($this->isClosed($indexName)) {
            return IndexStatus::CLOSED_STATUS;
        }

        if ($this->isRebuilding($indexName, $indexDate)) {
            return IndexStatus::REBUILDING_STATUS;
        }

        if ($this->isLive($alias)) {
            return IndexStatus::LIVE_STATUS;
        }

        if ($this->isGhost($indexDate)) {
            return IndexStatus::GHOST_STATUS;
        }

        return IndexStatus::UNDEFINED_STATUS;
    }

    /**
     * Returns if index is rebuilding.
     *
     * @param string         $indexName Index name.
     * @param DateTime|false $indexDate Index updated date.
     * @return bool
     * @throws Exception
     */
    private function isRebuilding(string $indexName, $indexDate): bool
    {
        if ($indexDate === false) {
            // If $indexDate is false, we cannot rebuild.
            return false;
        }

        if (!empty($this->workingIndexers)) {
            foreach (array_keys($this->workingIndexers) as $indexKey) {
                if (strpos((string) $indexName, $indexKey) !== false) {
                    $today = new DateTime('now');

                    return ($today->format('Y-m-d') === $indexDate->format('Y-m-d'));
                }
            }
        }

        return false;
    }

    /**
     * Returns if index is external.
     *
     * @param string $indexName Index name.
     *
     * @return bool
     */
    private function isExternal(string $indexName): bool
    {
        foreach ($this->storeIndices as $store) {
            if (strpos((string) $indexName, $store['pattern']) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns if the index is closed.
     *
     * @param string $indexName Index name.
     *
     * @return bool
     */
    private function isClosed(string $indexName): bool
    {
        // Ensure the index is NOT External before checking for Closed status.
        if ($this->isExternal($indexName)) {
            return false;
        }

        // If the index name does not exist in the global response, it is probably closed.
        if (!array_key_exists($indexName, $this->indicesStats)) {
            return true;
        }

        return false;
    }

    /**
     * Returns if index is ghost.
     *
     * @param DateTime $indexDate Index updated date.
     *
     * @return bool
     */
    private function isGhost($indexDate): bool
    {
        try {
            $indexDate = ($indexDate instanceof DateTime) ? $indexDate : new DateTime();
            $indexTimestamp = $indexDate->getTimestamp();

            return ((new DateTime())->getTimestamp() - $indexTimestamp) >= $this->indexSettingsHelper->getTimeBeforeGhost();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns if index is live.
     *
     * @param string $alias Index alias.
     * @return bool
     */
    private function isLive($alias): bool
    {
        return !empty($alias);
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
}
