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
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Search\Setup\InstallConfigInterface;
use Magento\Setup\Model\SearchConfigOptionsList;

/**
 * Installer Configuration
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class InstallConfig implements InstallConfigInterface
{
    /**
     * Catalog search parameters
     */
    private const CATALOG_SEARCH = 'catalog/search/';

    /**
     * Elasticsearch client parameters
     */
    private const ES_CLIENT = 'smile_elasticsuite_core_base_settings/es_client/';

    /**
     * Elasticsearch indices parameters
     */
    private const ES_INDICES = 'smile_elasticsuite_core_base_settings/indices_settings/';

    /**
     * @var array
     */
    private $searchConfigMapping = [
        SearchConfigOptionsList::INPUT_KEY_SEARCH_ENGINE => 'engine',
    ];

    /**
     * @var array
     */
    private $legacyClientMapping = [];

    /**
     * @var array
     */
    private $legacyIndicesMapping = [];

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * Constructor
     *
     * @param WriterInterface $configWriter         Config Writer
     * @param array           $searchConfigMapping  Search Config Mapping
     * @param array           $legacyClientMapping  Legacy Elasticsearch implementation mapping
     * @param array           $legacyIndicesMapping Legacy Elasticsearch indices mapping
     */
    public function __construct(
        WriterInterface $configWriter,
        array $searchConfigMapping = [],
        array $legacyClientMapping = [],
        array $legacyIndicesMapping = []
    ) {
        $this->configWriter         = $configWriter;
        $this->searchConfigMapping  = array_merge($this->searchConfigMapping, $searchConfigMapping);
        $this->legacyClientMapping  = array_merge($this->legacyClientMapping, $legacyClientMapping);
        $this->legacyIndicesMapping = array_merge($this->legacyIndicesMapping, $legacyIndicesMapping);
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function configure(array $inputOptions)
    {
        foreach ($inputOptions as $inputKey => $inputValue) {
            if (null !== $inputValue && (isset($this->searchConfigMapping[$inputKey]))) {
                $configKey = $this->searchConfigMapping[$inputKey];
                $this->configWriter->save(self::CATALOG_SEARCH . $configKey, $inputValue);
            }
            if (null !== $inputValue && (isset($this->legacyClientMapping[$inputKey]))) {
                $configKey = $this->legacyClientMapping[$inputKey];
                $this->configWriter->save(self::ES_CLIENT . $configKey, $inputValue);
            }
            if (null !== $inputValue && (isset($this->legacyIndicesMapping[$inputKey]))) {
                $configKey = $this->legacyIndicesMapping[$inputKey];
                $this->configWriter->save(self::ES_INDICES . $configKey, $inputValue);
            }
        }

        if (isset($inputOptions['elasticsearch-host']) && isset($inputOptions['elasticsearch-port'])) {
            $esHosts = sprintf('%s:%s', $inputOptions['elasticsearch-host'], $inputOptions['elasticsearch-port']);
            $this->configWriter->save(self::ES_CLIENT . 'servers', $esHosts);
        }

        if (isset($inputOptions['opensearch-host']) && isset($inputOptions['opensearch-port'])) {
            $esHosts = sprintf('%s:%s', $inputOptions['opensearch-host'], $inputOptions['opensearch-port']);
            $this->configWriter->save(self::ES_CLIENT . 'servers', $esHosts);
        }
    }
}
