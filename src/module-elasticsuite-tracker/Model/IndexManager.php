<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Model;

use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\ResourceModel\SessionIndex;

/**
 * Tracking Indices Manager
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IndexManager
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private $client;

    /**
     * Constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface               $storeManager  Store Manager.
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings Index settings.
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientInterface       $client        ES client.
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface $indexSettings,
        \Smile\ElasticsuiteCore\Api\Client\ClientInterface $client
    ) {
        $this->storeManager  = $storeManager;
        $this->client        = $client;
        $this->indexSettings = $indexSettings;
    }

    /**
     * Create an event index if not exists.
     *
     * @param \Smile\ElasticsuiteCore\Api\Index\IndexInterface $index Index.
     * @param int                                              $store Store id.
     *
     * @return void
     */
    public function createIndexIfNotExists(\Smile\ElasticsuiteCore\Api\Index\IndexInterface $index, $store)
    {
        if ($this->client->indexExists($index->getName()) === false) {
            $indexSettings = array_merge($this->indexSettings->getCreateIndexSettings(), $this->indexSettings->getInstallIndexSettings());
            $indexSettings += $this->indexSettings->getDynamicIndexSettings($store);
            $indexSettings['analysis'] = $this->indexSettings->getAnalysisSettings($store);
            $this->client->createIndex($index->getName(), ['settings' => $indexSettings]);
            $this->client->updateAliases([['add' => ['index' => $index->getName(), 'alias' => $index->getIdentifier()]]]);
            $this->client->putMapping($index->getName(), $index->getMapping()->asArray());
        }
    }

    /**
     * Keep only the last $number tracking indices.
     *
     * @param int $number Number of indices to keep.
     *
     * @return void
     */
    public function keepLatest(int $number)
    {
        if ($number === 0) {
            return;
        }

        $indexIdentifiers = [SessionIndexInterface::INDEX_IDENTIFIER, EventIndexInterface::INDEX_IDENTIFIER];

        foreach ($indexIdentifiers as $indexIdentifier) {
            foreach ($this->storeManager->getStores() as $store) {
                $indexAlias = $this->getIndexAlias($indexIdentifier, $store->getId());
                $indices    = $this->client->getIndicesNameByAlias($indexAlias);
                arsort($indices); // Sort horodated indices. Oldest will be at the end.
                $indicesToDelete = array_slice($indices, $number);

                if (!empty($indicesToDelete)) {
                    // In case of many indices existing, chunk the process.
                    foreach (array_chunk($indicesToDelete, 10) as $deleteChunk) {
                        $this->client->deleteIndex(implode(',', $deleteChunk));
                    }
                }
            }
        }
    }

    /**
     * Returns a [from, to] date range of available data based on horodated indices presence.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     *
     * @return \DateTime[]
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getIndicesDateBounds($indexIdentifier, $storeId)
    {
        $bounds = [];

        $indexAlias = $this->getIndexAlias($indexIdentifier, $storeId);
        $indices    = $this->client->getIndicesNameByAlias($indexAlias);

        if (!empty($indices)) {
            arsort($indices); // Sort horodated indices. Oldest will be at the end.
            $latest     = current($indices);
            $earliest   = array_pop($indices);

            $latestDate     = \DateTime::createFromFormat('Ymd', str_replace("{$indexAlias}_", "", $latest));
            $earliestDate   = \DateTime::createFromFormat('Ymd', str_replace("{$indexAlias}_", "", $earliest));

            $bounds = [$earliestDate, $latestDate];
        }

        return $bounds;
    }

    /**
     * Build index name from an event.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     * @param string $date            Date.
     *
     * @return string
     */
    public function getIndexName($indexIdentifier, $storeId, $date)
    {
        $indexAlias = $this->getIndexAlias($indexIdentifier, $storeId);
        $date       = substr($date, 0, 10);

        return sprintf("%s_%s", $indexAlias, str_replace("-", "", $date));
    }

    /**
     * Build index alias from an identifier & store.
     *
     * @param string $indexIdentifier Index identifier.
     * @param int    $storeId         Store id.
     *
     * @return string
     */
    public function getIndexAlias($indexIdentifier, $storeId)
    {
        return $this->indexSettings->getIndexAliasFromIdentifier($indexIdentifier, $storeId);
    }
}
