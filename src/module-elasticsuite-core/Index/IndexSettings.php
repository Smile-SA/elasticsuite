<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Index\Analysis\Config as AnalysisConfig;
use Smile\ElasticsuiteCore\Index\Indices\Config as IndicesConfig;

/**
 * This class provides an access to most index settings :
 *   - analysis
 *   - indices by identifier and related configuration
 *   - ...
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexSettings implements IndexSettingsInterface
{
    /**
     * @var string
     */
    const FULL_REINDEX_REFRESH_INTERVAL = '30s';

    /**
     * @var string
     */
    const DIFF_REINDEX_REFRESH_INTERVAL = '1s';

    /**
     * @var string
     */
    const FULL_REINDEX_TRANSLOG_DURABILITY = 'async';

    /**
     * @var string
     */
    const DIFF_REINDEX_TRANSLOG_DURABILITY = 'request';

    /**
     * @var integer
     */
    const MERGE_FACTOR = 20;

    /**
     * @var integer
     */
    const CODEC = 'best_compression';

    /**
     * @var integer
     */
    const TOTAL_FIELD_LIMIT = 20000;

    /**
     * @var \Smile\ElasticsuiteCore\Helper\IndexSettings
     */
    protected $helper;

    /**
     * @var \Smile\ElasticsuiteCore\Index\Analysis\Config
     */
    protected $analysisConfig;

    /**
     * @var \Smile\ElasticsuiteCore\Index\Indices\Config
     */
    protected $indicesConfig;

    /**
     * Constructor.
     *
     * @param IndexSettingsHelper $indexSettingHelper Index settings helper.
     * @param IndicesConfig       $indicesConfig      Indices configuration.
     * @param AnalysisConfig      $analysisConfig     Analysis configuration.
     */
    public function __construct(
        IndexSettingsHelper    $indexSettingHelper,
        IndicesConfig          $indicesConfig,
        AnalysisConfig         $analysisConfig
    ) {
        $this->helper         = $indexSettingHelper;
        $this->analysisConfig = $analysisConfig;
        $this->indicesConfig  = $indicesConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexAliasFromIdentifier($indexIdentifier, $store)
    {
        return $this->helper->getIndexAliasFromIdentifier($indexIdentifier, $store);
    }

    /**
     * {@inheritDoc}
     */
    public function createIndexNameFromIdentifier($indexIdentifier, $store)
    {
        return $this->helper->createIndexNameFromIdentifier($indexIdentifier, $store);
    }

    /**
     * {@inheritDoc}
     */
    public function getAnalysisSettings($store)
    {
        $language = $this->helper->getLanguageCode($store);

        return $this->analysisConfig->get($language);
    }

    /**
     * {@inheritDoc}
     */
    public function getCreateIndexSettings()
    {
        $settings = [
            'requests.cache.enable'            => true,
            'number_of_replicas'               => 0,
            'number_of_shards'                 => $this->helper->getNumberOfShards(),
            'refresh_interval'                 => self::FULL_REINDEX_REFRESH_INTERVAL,
            'merge.scheduler.max_thread_count' => 1,
            'translog.durability'              => self::FULL_REINDEX_TRANSLOG_DURABILITY,
            'codec'                            => self::CODEC,
            'max_result_window'                => $this->helper->getMaxResultWindow(),
            'mapping.total_fields.limit'       => self::TOTAL_FIELD_LIMIT,
        ];

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallIndexSettings()
    {
        $settings = [
            'number_of_replicas'     => $this->helper->getNumberOfReplicas(),
            'refresh_interval'       => self::DIFF_REINDEX_REFRESH_INTERVAL,
            'translog.durability'    => self::DIFF_REINDEX_TRANSLOG_DURABILITY,
        ];

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getIndicesConfig()
    {
        return $this->indicesConfig->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getIndexConfig($indexIdentifier)
    {
        $indicesConfig = $this->getIndicesConfig();

        if (!isset($indicesConfig[$indexIdentifier])) {
            throw new \LogicException("No indices found with identifier {$indexIdentifier}");
        }

        return $indicesConfig[$indexIdentifier];
    }

    /**
     * {@inheritDoc}
     */
    public function getBatchIndexingSize()
    {
        return $this->helper->getBatchIndexingSize();
    }
}
