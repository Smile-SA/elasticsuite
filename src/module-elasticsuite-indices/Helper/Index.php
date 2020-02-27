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
namespace Smile\ElasticsuiteIndices\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteCore\Client\Client;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Model\ResourceModel\StoreIndices\CollectionFactory as StoreIndicesCollectionFactory;
use Smile\ElasticsuiteIndices\Model\ResourceModel\WorkingIndexer\CollectionFactory as WorkingIndexerCollectionFactory;
use Zend_Date;
use Zend_Date_Exception;

/**
 * Smile Index helper
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Index extends AbstractHelper
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
     * Store number of seconds in a year.
     */
    private const SECONDS_IN_YEAR = 31536000;

    /**
     * @var array
     */
    private $elasticSuiteIndices = [
        'catalog_category',
        'catalog_product',
        'thesaurus',
    ];

    /**
     * @var Client
     */
    private $esClient;

    /**
     * @var DataObject
     */
    protected $workingIndexers;

    /**
     * @var DataObject
     */
    protected $storeIndices;

    /**
     * @var IndexSettings
     */
    private $indexSettings;

    /**
     * PHP Constructor
     *
     * @param Context                         $context                  The current context.
     * @param Client                          $esClient                 ElasticSearch client.
     * @param WorkingIndexerCollectionFactory $indexerCollectionFactory Working indexers collection.
     * @param StoreIndicesCollectionFactory   $storeIndicesFactory      Store indices collection.
     * @param IndexSettings                   $indexSettings            ElasticSuite index settings.
     */
    public function __construct(
        Context $context,
        Client $esClient,
        WorkingIndexerCollectionFactory $indexerCollectionFactory,
        StoreIndicesCollectionFactory $storeIndicesFactory,
        IndexSettings $indexSettings
    ) {
        parent::__construct($context);
        $this->esClient = $esClient;
        $this->workingIndexers = $indexerCollectionFactory->create()->getItems();
        $this->storeIndices = $storeIndicesFactory->create()->getItems();
        $this->indexSettings = $indexSettings;
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
        $elasticSuiteIndices = [];

        foreach ($this->esClient->getIndexAliases($params) as $name => $aliases) {
            if ($this->isElasticSuiteIndex($name)) {
                $elasticSuiteIndices[$name] = $aliases ? key($aliases['aliases']) : null;
            }
        }

        return $elasticSuiteIndices;
    }

    /**
     * Returns if index is elastic suite index.
     *
     * @param string $indexName Index name.
     * @return bool
     */
    public function isElasticSuiteIndex($indexName): bool
    {
        foreach ($this->elasticSuiteIndices as $elasticSuiteIndex) {
            if (strpos($indexName, $elasticSuiteIndex) !== false) {
                return true;
            }
        }

        return false;
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
     * Size formatted.
     *
     * @param string $bytes Bytes.
     *
     * @return string
     */
    public function sizeFormatted($bytes): string
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
        preg_match_all('/{{([\w]*)}}/', $this->indexSettings->getIndicesPattern(), $matches);

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
