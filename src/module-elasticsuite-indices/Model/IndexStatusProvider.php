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
use Magento\Framework\DataObject;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\ResourceModel\StoreIndices\CollectionFactory as StoreIndicesCollectionFactory;
use Smile\ElasticsuiteIndices\Model\ResourceModel\WorkingIndexer\CollectionFactory as WorkingIndexerCollectionFactory;
use Zend_Date;
use Zend_Date_Exception;

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
     * Store number of seconds in a year.
     */
    private const SECONDS_IN_YEAR = 31536000;

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
     * Constructor.
     *
     * @param ClientInterface                 $client                   ES client.
     * @param IndexSettingsHelper             $indexSettingsHelper      Index settings helper.
     * @param StoreIndicesCollectionFactory   $storeIndicesFactory      Store indices collection.
     * @param WorkingIndexerCollectionFactory $indexerCollectionFactory Working indexers collection.
     * @param LoggerInterface                 $logger                   Logger.
     */
    public function __construct(
        ClientInterface $client,
        IndexSettingsHelper $indexSettingsHelper,
        StoreIndicesCollectionFactory $storeIndicesFactory,
        WorkingIndexerCollectionFactory $indexerCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->storeIndices = $storeIndicesFactory->create()->getItems();
        $this->workingIndexers = $indexerCollectionFactory->create()->getItems();
        $this->logger = $logger;
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
        $indexDate = $this->getIndexUpdatedDateFromIndexName($indexName, $alias);

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
     * @param string          $indexName Index name.
     * @param Zend_Date|false $indexDate Index updated date.
     * @return bool
     */
    private function isRebuilding(string $indexName, $indexDate): bool
    {
        if ($indexDate === false) {
            // If $indexDate is false, we cannot rebuild.
            return false;
        }

        if (!empty($this->workingIndexers)) {
            foreach (array_keys($this->workingIndexers) as $indexKey) {
                if (strpos($indexName, $indexKey) !== false) {
                    return $indexDate->isToday();
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
            if (strpos($indexName, $store['pattern']) !== false) {
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
        try {
            // Ensure the index is NOT External before checking for Closed status.
            if ($this->isExternal($indexName)) {
                return false;
            }

            // Attempt to fetch index stats or metadata to check its status.
            $indexStats = $this->client->indexStats($indexName);

            // If we successfully retrieved index stats and no error occurs, the index is not closed.
            return false;
        } catch (Exception $e) {
            // Log the error (optional for better diagnostics).
            $this->logger->error(
                sprintf('Error fetching index stats for "%s": %s', $indexName, $e->getMessage())
            );

            // If an error occurs, it's safer to assume the index could be closed, or the stats are unavailable.
            // Returning true here means the index is likely closed or inaccessible.
            return true;
        }
    }

    /**
     * Returns if index is ghost.
     *
     * @param Zend_Date|false $indexDate Index updated date.
     * @return bool
     */
    private function isGhost($indexDate): bool
    {
        try {
            return (new Zend_Date())->sub($indexDate)->getTimestamp() >= $this->indexSettingsHelper->getTimeBeforeGhost();
        } catch (Zend_Date_Exception $e) {
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
     * Get index updated date from index name.
     *
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     *
     * @return Zend_Date|false
     */
    private function getIndexUpdatedDateFromIndexName($indexName, $alias)
    {
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $this->indexSettingsHelper->getIndicesPattern(), $matches);

        if (empty($matches[1])) {
            return false;
        }

        $count = 0;
        $format = '';
        foreach ($matches[1] as $value) {
            $count += strlen($value);
            $format .= $value;
        }

        try {
            // Remove alias from index name since next preg_replace would fail if alias is containing numbers.
            $indexName = str_replace($alias ?? '', '', $indexName);
            $date      = substr(preg_replace('/[^0-9]/', '', $indexName), -$count);

            // Tracking indices are built monthly and does not fit with standard pattern containing datetime with hours.
            if (strlen($date) !== 14) {
                return false;
            }

            return new Zend_Date($date, $format);
        } catch (Zend_Date_Exception $e) {
            return false;
        }
    }
}
