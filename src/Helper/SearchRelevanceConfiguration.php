<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Helper;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\SearchRelevanceConfigurationInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Search Relevance Configuration Helper
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SearchRelevanceConfiguration extends AbstractConfiguration
{
    /**
     * XML root node for search relevance
     */
    const BASE_RELEVANCE_CONFIG_XML_PREFIX = 'smile_elasticsuite_relevance';

    /**
     * XML node for phrase match configuration
     */
    const PHRASE_MATCH_CONFIG_XML_PREFIX = 'phrase_match_configuration';

    /**
     * XML node for cutoff frequency configuration
     */
    const CUTOFF_FREQUENCY_CONFIG_XML_PREFIX = 'cutoff_frequency_configuration';

    /**
     * XML node for fuzziness configuration
     */
    const FUZZINESS_CONFIG_XML_PREFIX = 'search_fuzziness_configuration';

    /**
     * XML node for phonetic configuration
     */
    const PHONETIC_CONFIG_XML_PREFIX = 'phonetic_configuration';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param Context                $context       Helper context.
     * @param StoreManagerInterface  $storeManager  Store manager.
     * @param ObjectManagerInterface $objectManager Object manager.
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
        parent::__construct($context, $storeManager);
    }

    /**
     * Retrieve Search Relevance Configuration
     *
     * @param int|string|\Magento\Store\Api\Data\StoreInterface $store     The store identifier or id.
     * @param string                                            $container The search request container
     *
     * @return SearchRelevanceConfigurationInterface
     */
    public function getSearchRelevanceConfiguration($store = null, $container = null)
    {
        $scope = $this->getScope($store, $container);
        $scopeCode = $this->getScopeCode($store, $container);

        $configurationParams = [
            'phraseMatchBoost' => $this->getPhraseMatchBoostConfiguration($scope, $scopeCode),
            'cutOffFrequency'  => $this->getCutoffFrequencyConfiguration($scope, $scopeCode),
            'fuzziness'        => $this->getFuzzinessConfiguration($scope, $scopeCode),
            'phonetic'         => $this->getPhoneticConfiguration($scope, $scopeCode),
        ];

        $configuration = $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\SearchRelevanceConfigurationInterface',
            $configurationParams
        );

        return $configuration;
    }

    /**
     * Get configuration value
     *
     * @param string $path      The config value path
     * @param string $scope     The configuration scope
     * @param string $scopeCode The Configuration Scope code
     *
     * @return mixed
     */
    public function getConfigValue($path, $scope, $scopeCode)
    {
        return $this->scopeConfig->getValue($path, $scope, $scopeCode);
    }

    /**
     * @param string $scope     The scope
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getPhraseMatchBoostConfiguration($scope, $scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::PHRASE_MATCH_CONFIG_XML_PREFIX;

        $enabled = (bool) $this->getConfigValue($path . "/enable_phrase_match", $scope, $scopeCode);

        if (!$enabled) {
            return $enabled;
        }

        $boost = (int) $this->getConfigValue($path . "/phrase_match_boost_value", $scope, $scopeCode);

        return $boost;
    }

    /**
     * Retrieve Cutoff Frequency
     *
     * @param string $scope     The scope
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getCutoffFrequencyConfiguration($scope, $scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::CUTOFF_FREQUENCY_CONFIG_XML_PREFIX;

        return (float) $this->getConfigValue($path . "/cutoff_frequency", $scope, $scopeCode);
    }

    /**
     * Retrieve fuzziness configuration object
     *
     * @param string $scope     The scope
     * @param string $scopeCode The scope code
     *
     * @return \Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface|null
     */
    private function getFuzzinessConfiguration($scope, $scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::FUZZINESS_CONFIG_XML_PREFIX;

        $enabled = (bool) $this->getConfigValue($path . "/enable_fuzziness", $scope, $scopeCode);

        if (!$enabled) {
            return null;
        }

        $configurationParams = [
            'value'        => $this->getConfigValue($path . "/fuzziness_value", $scope, $scopeCode),
            'prefixLength' => $this->getConfigValue($path . "/fuzziness_prefix_length", $scope, $scopeCode),
            'maxExpansion' => $this->getConfigValue($path . "/fuzziness_max_expansion", $scope, $scopeCode),
        ];

        $configuration = $this->createFuzzinessConfiguration($configurationParams);

        return $configuration;
    }

    /**
     * Retrieve phonetic configuration object
     *
     * @param string $scope     The scope
     * @param string $scopeCode The scope code
     *
     * @return \Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface|null
     */
    private function getPhoneticConfiguration($scope, $scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::PHONETIC_CONFIG_XML_PREFIX;

        $enabled = (bool) $this->getConfigValue($path . "/enable_phonetic_search", $scope, $scopeCode);

        if (!$enabled) {
            return null;
        }

        $phoneticFuzziness = (bool) $this->getConfigValue($path . "/enable_phonetic_fuzziness", $scope, $scopeCode);

        $configurationParams = ['fuzziness' => null];

        if ($phoneticFuzziness) {
            $path .= "/phonetic_";
            $fuzzinessParams = [
                'value'        => $this->getConfigValue($path . "fuzziness_value", $scope, $scopeCode),
                'prefixLength' => $this->getConfigValue($path . "fuzziness_prefix_length", $scope, $scopeCode),
                'maxExpansion' => $this->getConfigValue($path . "fuzziness_max_expansion", $scope, $scopeCode),
            ];

            $configurationParams['fuzziness'] = $this->createFuzzinessConfiguration($fuzzinessParams);
        }

        return $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\PhoneticConfigurationInterface',
            $configurationParams
        );
    }

    /**
     * Create a Fuzziness Configuration Object
     *
     * @param array $configurationParams Object parameters
     *
     * @return \Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface
     */
    private function createFuzzinessConfiguration($configurationParams)
    {
        return $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\SearchRelevanceConfiguration\FuzzinessConfigurationInterface',
            $configurationParams
        );
    }

    /**
     * Retrieve the store code from object or store id.
     *
     * @param \Magento\Store\Api\Data\StoreInterface|integer $store The store or it's id.
     *
     * @return string
     */
    private function getStoreId($store)
    {
        return $this->getStore($store)->getId();
    }

    /**
     * Ensure store is an object or load it from it's id / identifier.
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store The store identifier or id.
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    private function getStore($store)
    {
        if (!is_object($store)) {
            $store = $this->storeManager->getStore($store);
        }

        return $store;
    }

    /**
     * Retrieve current scope
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store     The store identifier or id.
     * @param string|null                                           $container The container
     *
     * @return string
     */
    private function getScope($store, $container)
    {
        $scope = \Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface::SCOPE_TYPE_DEFAULT;

        if ($container !== null) {
            $scope = \Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface::SCOPE_CONTAINERS;
            if ($store !== null) {
                $scope = \Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface::SCOPE_STORE_CONTAINERS;
            }
        }

        return $scope;
    }

    /**
     * Retrieve current scope code
     *
     * @param integer|string|\Magento\Store\Api\Data\StoreInterface $store     The store identifier or id.
     * @param string|null                                           $container The container
     *
     * @return string
     */
    private function getScopeCode($store, $container)
    {
        $scopeCode = \Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface::SCOPE_TYPE_DEFAULT;

        if ($container !== null) {
            $scopeCode = $container;
            if ($store !== null) {
                $scopeCode .= "|" . $this->getStoreId($store);
            }
        }

        return $scopeCode;
    }
}
