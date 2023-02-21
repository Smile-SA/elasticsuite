<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;

/**
 * Generic installer for ElasticsuiteCore.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CoreSetup
{
    /**
     * @var string
     */
    const INDICES_SETTINGS_CONFIG_XML_INDICES_PATTERN_PATH = 'smile_elasticsuite_core_base_settings/indices_settings/indices_pattern';

    /**
     * @var IndexSettingsHelper
     */
    protected $indexSettingsHelper;

    /**
     * @var Config
     */
    protected $resourceConfig;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * Class Constructor.
     *
     * @param IndexSettingsHelper $indexSettingsHelper Index settings helper.
     * @param Config              $resourceConfig      Resource config.
     * @param TypeListInterface   $cacheTypeList       Cache type list.
     */
    public function __construct(
        IndexSettingsHelper $indexSettingsHelper,
        Config $resourceConfig,
        TypeListInterface $cacheTypeList
    ) {
        $this->indexSettingsHelper = $indexSettingsHelper;
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Update Default Indices Pattern.
     *
     * @return void
     */
    public function updateDefaultIndicesPattern(): void
    {
        $indexNameSuffix = $this->indexSettingsHelper->getIndicesSettingsConfigParam('indices_pattern');
        if ($indexNameSuffix === IndexSettingsHelper::OLD_DEFAULT_INDICES_PATTERN) {
            $indexNameSuffix = IndexSettingsHelper::DEFAULT_INDICES_PATTERN;
            $this->resourceConfig->saveConfig(self::INDICES_SETTINGS_CONFIG_XML_INDICES_PATTERN_PATH, $indexNameSuffix);
            $this->cacheTypeList->cleanType(ConfigCache::TYPE_IDENTIFIER);
        }
    }
}
