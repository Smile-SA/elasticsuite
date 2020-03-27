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

use Magento\Framework\DataObject;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\ResourceModel\StoreIndices\CollectionFactory as StoreIndicesCollectionFactory;
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
     * @var IndexSettingsHelper
     */
    private $indexSettingsHelper;

    /**
     * @var DataObject
     */
    protected $storeIndices;

    /**
     * Constructor.
     *
     * @param IndexSettingsHelper           $indexSettingsHelper Index settings helper.
     * @param StoreIndicesCollectionFactory $storeIndicesFactory Store indices collection.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingsHelper,
        StoreIndicesCollectionFactory $storeIndicesFactory
    ) {
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->storeIndices = $storeIndicesFactory->create()->getItems();
    }

    /**
     * Get a index status.
     *
     * @param string $indexName Index name.
     * @param string $alias     Index alias.
     *
     * @return string
     */
    public function getIndexStatus($indexName, $alias): string
    {
        $indexDate = $this->getIndexUpdatedDateFromIndexName($indexName);

        if ($this->isExternal($indexName, $indexDate)) {
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
     * @param string          $indexName Index name.
     * @param Zend_Date|false $indexDate Index updated date.
     * @return bool
     */
    private function isRebuilding(string $indexName, $indexDate): bool
    {
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
     * @param string          $indexName Index name.
     * @param Zend_Date|false $indexDate Index updated date.
     * @return bool
     */
    private function isExternal(string $indexName, $indexDate): bool
    {
        foreach ($this->storeIndices as $store) {
            if (strpos($indexName, $store['pattern']) !== false) {
                return false;
            }
            if (!$this->isValidDate($indexDate)) {
                return false;
            }
        }

        return true;
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
            return (new Zend_Date())->sub($indexDate)->getTimestamp() / self::SECONDS_IN_DAY >= self::NUMBER_DAYS_AFTER_INDEX_IS_GHOST;
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
     * @return Zend_Date|false
     */
    private function getIndexUpdatedDateFromIndexName($indexName)
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
            return new Zend_Date(substr(preg_replace('/[^0-9]/', '', $indexName), -$count), $format);
        } catch (Zend_Date_Exception $e) {
            return false;
        }
    }

    /**
     * Returns if date is valid.
     *
     * Rules: Year should not be in the future and not older than five year.
     *
     * @param Zend_Date|false $date Index updated date.
     * @return bool
     */
    private function isValidDate($date): bool
    {
        if ($date === false) {
            return false;
        }
        $currentDate = new Zend_Date();

        return $currentDate->sub($date)->getTimestamp() / self::SECONDS_IN_YEAR > 0
            && $currentDate->sub($date)->getTimestamp() / self::SECONDS_IN_YEAR < 5;
    }
}
