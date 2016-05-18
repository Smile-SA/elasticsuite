<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\RelevanceConfig;

use Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticSuiteCore\Api\Search\Request\ContainerScopeInterface;
use Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface;

/**
 * Search relevance configuration factory.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Factory
{
    /**
     * XML root node for search relevance
     */
    const BASE_RELEVANCE_CONFIG_XML_PREFIX = 'relevance';

    /**
     * XML node for phrase match configuration
     */
    const PHRASE_MATCH_CONFIG_XML_PREFIX = 'phrase_match_configuration';

    /**
     * XML node for minimum should match configuration.
     */
    const MINIMUM_SHOULD_MATCH_CONFIG_XML_PATH = 'fulltext_base_settings/minimum_should_match';

    /**
     * XML node for tie breaker configuration.
     */
    const TIE_BREAKER_CONFIG_XML_PATH = 'fulltext_base_settings/tie_breaker';

    /**
     * XML node for cutoff frequency configuration
     */
    const CUTOFF_FREQUENCY_CONFIG_XML_PATH = 'cutoff_frequency_configuration/cutoff_frequency';

    /**
     * XML node for fuzziness configuration
     */
    const FUZZINESS_CONFIG_XML_PREFIX = 'spellchecking/fuzziness';

    /**
     * XML node for phonetic configuration
     */
    const PHONETIC_CONFIG_XML_PREFIX = 'spellchecking/phonetic';

    /**
     * @var RelevanceConfigurationInterface[]
     */
    private $cachedConfig = [];

    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\RelevanceConfig\App\Config
     */
    private $scopeConfig;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param string                 $instanceName  Config class name.
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = 'Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface'
    ) {
        $this->scopeConfig   = $objectManager->get('Smile\ElasticSuiteCore\Search\Request\RelevanceConfig\App\Config');
        $this->objectManager = $objectManager;
        $this->instanceName  = $instanceName;
    }

    /**
     * Retrieve relevance configuration for a container.
     *
     * @param int    $storeId       Store id.
     * @param string $containerName Container identifier.
     *
     * @return \Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfigurationInterface
     */
    public function create($storeId, $containerName)
    {
        $scopeCode = $this->getScopeCode($storeId, $containerName);

        if (!isset($this->cachedConfig[$scopeCode])) {
            $instanceConfiguration          = $this->loadConfiguration($scopeCode);
            $this->cachedConfig[$scopeCode] = $this->objectManager->create($this->instanceName, $instanceConfiguration);
        }

        return $this->cachedConfig[$scopeCode];
    }

    /**
     * Load the relevance configuration by scope code.
     *
     * @param string $scopeCode Container scope code.
     *
     * @return array
     */
    protected function loadConfiguration($scopeCode)
    {
        $configurationParams = [
            'minimumShouldMatch' => $this->getMinimumShouldMatch($scopeCode),
            'tieBreaker'         => $this->getTieBreaker($scopeCode),
            'phraseMatchBoost'   => $this->getPhraseMatchBoostConfiguration($scopeCode),
            'cutOffFrequency'    => $this->getCutoffFrequencyConfiguration($scopeCode),
            'fuzziness'          => $this->getFuzzinessConfiguration($scopeCode),
            'phonetic'           => $this->getPhoneticConfiguration($scopeCode),
        ];

        return $configurationParams;
    }

    /**
     * Read value into the config by path and scope.
     *
     * @param string $path      Config path.
     * @param string $scopeCode Scope coode.
     *
     * @return mixed
     */
    protected function getConfigValue($path, $scopeCode)
    {
        $scope = ContainerScopeInterface::SCOPE_STORE_CONTAINERS;

        return $this->scopeConfig->getValue($path, $scope, $scopeCode);
    }

    /**
     * Retrieve fuzziness configuration object.
     *
     * @param string $scopeCode The scope code.
     *
     * @return FuzzinessConfigurationInterface|null
     */
    private function getFuzzinessConfiguration($scopeCode)
    {
        $path = self::FUZZINESS_CONFIG_XML_PREFIX;

        $configuration = (bool) $this->getConfigValue($path . "/enable", $scopeCode);

        if ($configuration === true) {
            $configurationParams = [
                'value'        => $this->getConfigValue($path . "/value", $scopeCode),
                'prefixLength' => $this->getConfigValue($path . "/prefix_length", $scopeCode),
                'maxExpansion' => $this->getConfigValue($path . "/max_expansion", $scopeCode),
            ];

            $configuration = $this->createFuzzinessConfiguration($configurationParams);
        }

        return $configuration === false ? null : $configuration;
    }

    /**
     * Retrieve phonetic configuration object
     *
     * @param string $scopeCode The scope code.
     *
     * @return PhoneticConfigurationInterface|null
     */
    private function getPhoneticConfiguration($scopeCode)
    {
        $path = self::PHONETIC_CONFIG_XML_PREFIX;

        $configuration = (bool) $this->getConfigValue($path . "/enable", $scopeCode);

        if ($configuration) {
            $phoneticFuzziness = (bool) $this->getConfigValue($path . "/enable_fuzziness", $scopeCode);
            $configurationParams = ['fuzziness' => null];

            if ($phoneticFuzziness === true) {
                $fuzzinessParams = [
                    'value'        => $this->getConfigValue($path . "/fuzziness_value", $scopeCode),
                    'prefixLength' => $this->getConfigValue($path . "/fuzziness_prefix_length", $scopeCode),
                    'maxExpansion' => $this->getConfigValue($path . "/fuzziness_max_expansion", $scopeCode),
                ];

                $configurationParams['fuzziness'] = $this->createFuzzinessConfiguration($fuzzinessParams);
            }

            $configuration = $this->objectManager->create(
                '\Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfiguration\PhoneticConfigurationInterface',
                $configurationParams
            );
        }

        return $configuration === false ? null : $configuration;
    }

    /**
     * Create a Fuzziness Configuration Object
     *
     * @param array $configurationParams Object parameters
     *
     * @return FuzzinessConfigurationInterface
     */
    private function createFuzzinessConfiguration($configurationParams)
    {
        return $this->objectManager->create(
            '\Smile\ElasticSuiteCore\Api\Search\Request\Container\RelevanceConfiguration\FuzzinessConfigurationInterface',
            $configurationParams
        );
    }

    /**
     * Retrieve phrase boost configuration for a container.
     *
     * @param string $scopeCode The scope code
     *
     * @return bool|int
     */
    private function getPhraseMatchBoostConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::PHRASE_MATCH_CONFIG_XML_PREFIX;

        $boost = (bool) $this->getConfigValue($path . "/enable_phrase_match", $scopeCode);

        if ($boost === true) {
            $boost = (int) $this->getConfigValue($path . "/phrase_match_boost_value", $scopeCode);
        }

        return $boost;
    }

    /**
     * Retrieve minimum should match config for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return string
     */
    private function getMinimumShouldMatch($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::MINIMUM_SHOULD_MATCH_CONFIG_XML_PATH;

        return $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve tie breaker config for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return float
     */
    private function getTieBreaker($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::TIE_BREAKER_CONFIG_XML_PATH;

        return (float) $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve cutoff frequency for a container.
     *
     * @param string $scopeCode The scope code.
     *
     * @return float
     */
    private function getCutoffFrequencyConfiguration($scopeCode)
    {
        $path = self::BASE_RELEVANCE_CONFIG_XML_PREFIX . "/" . self::CUTOFF_FREQUENCY_CONFIG_XML_PATH;

        return (float) $this->getConfigValue($path, $scopeCode);
    }

    /**
     * Retrieve current scope code
     *
     * @param integer     $storeId       The store identifier or id.
     * @param string|null $containerName The container name.
     *
     * @return string
     */
    private function getScopeCode($storeId, $containerName)
    {
        return sprintf("%s|%s", $containerName, $storeId);
    }
}
