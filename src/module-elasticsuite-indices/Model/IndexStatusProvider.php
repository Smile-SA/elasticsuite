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

use DateInterval;
use DateTime;
use Exception;
use Magento\Framework\DataObject;
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
     * @param IndexSettingsHelper             $indexSettingsHelper      Index settings helper.
     * @param StoreIndicesCollectionFactory   $storeIndicesFactory      Store indices collection.
     * @param WorkingIndexerCollectionFactory $indexerCollectionFactory Working indexers collection.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingsHelper,
        StoreIndicesCollectionFactory $storeIndicesFactory,
        WorkingIndexerCollectionFactory $indexerCollectionFactory
    ) {
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->storeIndices = $storeIndicesFactory->create()->getItems();
        $this->workingIndexers = $indexerCollectionFactory->create()->getItems();
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
        if (!empty($this->workingIndexers)) {
            foreach (array_keys($this->workingIndexers) as $indexKey) {
                if (strpos((string) $indexName, $indexKey) !== false) {
                    $today = new DateTime('now');

                    return ($today == $indexDate);
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

            return (new DateTime())->diff($indexDate)->days >= self::NUMBER_DAYS_AFTER_INDEX_IS_GHOST;
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
     * Get index updated date from index name.
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     *
     * @return DateTime|false
     */
    private function getIndexUpdatedDateFromIndexName($indexName, $alias)
    {
        $matches = [];
        preg_match_all('/{{([\w]*)}}/', $this->indexSettingsHelper->getIndicesPattern(), $matches);

        if (empty($matches[1])) {
            return false;
        }

        $format = '';
        foreach ($matches[1] as $value) {
            $format .= $value;
        }

        try {
            // Remove alias from index name since next preg_replace would fail if alias is containing numbers.
            $indexName = str_replace($alias ?? $this->indexSettingsHelper->getIndexAlias(), '', $indexName);
            $date      = preg_replace('/[^0-9]|(?<=[a-zA-Z])[0-9]/', '', $indexName);

            return DateTime::createFromFormat($format, $date);
        } catch (Exception $e) {
            return false;
        }
    }
}
