<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Index;

use Smile\ElasticSuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticSuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticSuiteCore\Index\Analysis\Config as AnalysisConfig;
use Smile\ElasticSuiteCore\Index\Indices\Config as IndicesConfig;

/**
 * This class provides an access to most index settings :
 *   - analysis
 *   - indices by identifier and related configuration
 *   - ...
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexSettings implements IndexSettingsInterface
{
    /**
     * @var string
     */
    const FULL_REINDEX_REFRESH_INTERVAL = '10s';

    /**
     * @var string
     */
    const DIFF_REINDEX_REFRESH_INTERVAL = '1s';

    /**
     * @var integer
     */
    const MERGE_FACTOR = 20;


    /**
     * @var \Smile\ElasticSuiteCore\Helper\IndexSettings
     */
    protected $helper;

    /**
     * @var \Smile\ElasticSuiteCore\Analysis\Config
     */
    protected $analysisConfig;

    /**
     * @var \Smile\ElasticSuiteCore\Indices\Config
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
            'number_of_replicas'        => 0,
            'number_of_shards'          => $this->helper->getNumberOfShards(),
            'refresh_interval'          => self::FULL_REINDEX_REFRESH_INTERVAL,
            'merge.policy.merge_factor' => self::MERGE_FACTOR,
        ];

        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallIndexSettings()
    {
        $settings = [
            'number_of_replicas' => $this->helper->getNumberOfReplicas(),
            'refresh_interval'   => self::DIFF_REINDEX_REFRESH_INTERVAL,
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
