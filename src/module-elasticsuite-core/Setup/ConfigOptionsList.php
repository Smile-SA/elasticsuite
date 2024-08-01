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
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\Option\SelectConfigOption;

/**
 * Handle ES parameters during setup.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Global prefix of config in the env file.
     */
    const CONF_PREFIX = 'system/default/smile_elasticsuite_core_base_settings/es_client';

    /**
     * Input key for the options
     */
    const INPUT_KEY_ES_HOSTS = 'es-hosts';
    const INPUT_KEY_ES_SSL   = 'es-enable-ssl';
    const INPUT_KEY_ES_USER  = 'es-user';
    const INPUT_KEY_ES_PASS  = 'es-pass';

    /**
     * Path to the values in the deployment config
     */
    const CONFIG_PATH_ES_HOSTS = self::CONF_PREFIX . '/servers';
    const CONFIG_PATH_ES_SSL   = self::CONF_PREFIX . '/enable_https_mode';
    const CONFIG_PATH_ES_USER  = self::CONF_PREFIX . '/http_auth_user';
    const CONFIG_PATH_ES_PASS  = self::CONF_PREFIX . '/http_auth_pwd';

    /**
     * @var \Smile\ElasticsuiteCore\Client\ClientBuilder
     */
    private $clientBuilder;

    /**
     * @var array
     */
    private $fallbackMapping = [
        self::INPUT_KEY_ES_USER => SearchConfigOptionsList::INPUT_KEY_ELASTICSEARCH_USERNAME,
        self::INPUT_KEY_ES_PASS => SearchConfigOptionsList::INPUT_KEY_ELASTICSEARCH_PASSWORD,
    ];

    /**
     * @var \Smile\ElasticsuiteCore\Setup\SearchConfigOptionsList
     */
    private $searchConfigOptionsList;

    /**
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Client\ClientBuilder $clientBuilder           ES client builder.
     * @param SearchConfigOptionsList                      $searchConfigOptionsList Legacy Magento options for search.
     * @param array                                        $fallbackMapping         Fallback Mapping for configuration.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Client\ClientBuilder $clientBuilder,
        SearchConfigOptionsList $searchConfigOptionsList,
        $fallbackMapping = []
    ) {
        $this->clientBuilder           = $clientBuilder;
        $this->fallbackMapping         = array_merge($this->fallbackMapping, $fallbackMapping);
        $this->searchConfigOptionsList = $searchConfigOptionsList;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return array_merge(
            [
                new TextConfigOption(
                    self::INPUT_KEY_ES_HOSTS,
                    TextConfigOption::FRONTEND_WIZARD_TEXT,
                    self::CONFIG_PATH_ES_HOSTS,
                    'ElasticSearch Servers List.'
                ),
                new SelectConfigOption(
                    self::INPUT_KEY_ES_SSL,
                    SelectConfigOption::FRONTEND_WIZARD_SELECT,
                    [0, 1],
                    self::CONFIG_PATH_ES_SSL,
                    'Use SSL mode to connect to ElasticSearch.',
                    0
                ),
                new TextConfigOption(
                    self::INPUT_KEY_ES_USER,
                    TextConfigOption::FRONTEND_WIZARD_TEXT,
                    self::CONFIG_PATH_ES_USER,
                    'ElasticSearch User Name.'
                ),
                new TextConfigOption(
                    self::INPUT_KEY_ES_PASS,
                    TextConfigOption::FRONTEND_WIZARD_TEXT,
                    self::CONFIG_PATH_ES_PASS,
                    'ElasticSearch password.'
                ),
            ],
            $this->searchConfigOptionsList->getOptionsList() // Legacy options are injected here to be available in validate().
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        $clientOptions = $this->getClientOptions($options, $deploymentConfig);
        foreach ($clientOptions as $optionName => $value) {
            $configData->set(self::CONF_PREFIX . '/' . $optionName, $value);
        }

        return [$configData];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        try {
            $options = array_filter($this->getClientOptions($options, $deploymentConfig));

            $this->clientBuilder->build($options)->info();
        } catch (\Exception $e) {
            $errors[] = "ElasticSuite : Unable to configure connection to Elasticsearch server : {$e->getMessage()}";
        }

        return $errors;
    }

    /**
     * Read client options from CLI / env file.
     *
     * @param array            $options          Input options.
     * @param DeploymentConfig $deploymentConfig Deployment config.
     *
     * @return array
     */
    private function getClientOptions(array $options, DeploymentConfig $deploymentConfig)
    {
        // phpcs:disable Squiz.WhiteSpace.OperatorSpacing.SpacingBefore
        $clientOptions = [
            'servers' => $this->getServers($options, $deploymentConfig),
            'enable_https_mode' => $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_SSL)
                ?: $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_OPENSEARCH_ENABLE_AUTH),
            'http_auth_user' => (string) $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_USER)
                ?: (string) $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_OPENSEARCH_USERNAME),
            'http_auth_pwd' => (string) $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_PASS)
                ?: (string) $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_OPENSEARCH_PASSWORD),
        ];
        // phpcs:enable Squiz.WhiteSpace.OperatorSpacing.SpacingBefore

        $clientOptions['enable_http_auth'] = !empty($clientOptions['http_auth_user']) && !empty($clientOptions['http_auth_pwd']);

        return $clientOptions;
    }

    /**
     * Get servers configuration. We try to fetch them from our own "es-hosts" parameters but allow a fallback to
     * Magento parameters "elasticsearch-host" and "elasticsearch-port".
     *
     * @param array            $options          Input options.
     * @param DeploymentConfig $deploymentConfig Deployment config.
     *
     * @return mixed|string|null
     */
    private function getServers($options, DeploymentConfig $deploymentConfig)
    {
        $servers = $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_HOSTS);

        if (null === $servers) {
            // Fallback to legacy Magento2 parameters.
            // phpcs:disable Squiz.WhiteSpace.OperatorSpacing.SpacingBefore
            $server = $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_ELASTICSEARCH_HOST)
                ?: $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_OPENSEARCH_HOST);
            $port = $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_ELASTICSEARCH_PORT)
                ?: $this->readConfiguration($options, $deploymentConfig, SearchConfigOptionsList::INPUT_KEY_OPENSEARCH_PORT);
            // phpcs:enable Squiz.WhiteSpace.OperatorSpacing.SpacingBefore

            if ($server && $port) {
                $servers = sprintf('%s:%s', $server, $port);
            }
        }

        return $servers;
    }

    /**
     * Read a value from the input with fallback to the current deployment config.
     *
     * @param array            $options          Input options.
     * @param DeploymentConfig $deploymentConfig Deployment config.
     * @param string           $inputKey         Name of the variable in the input options.
     *
     * @return mixed
     */
    private function readConfiguration(array $options, DeploymentConfig $deploymentConfig, $inputKey)
    {
        $config = null;
        $option = $this->getOption($inputKey);

        if ($option) {
            $configPath = $option->getConfigPath($inputKey);
            $config = $options[$inputKey] ?? ($configPath != null ? $deploymentConfig->get($configPath) : $option->getDefault());

            if (!$config && (array_key_exists($inputKey, $this->fallbackMapping))) {
                $config = $this->readConfiguration($options, $deploymentConfig, $this->fallbackMapping[$inputKey]);
            }
        }

        return $config;
    }

    /**
     * Retrieve option by input key.
     *
     * @param string $inputKey Input key.
     *
     * @return \Magento\Framework\Setup\Option\AbstractConfigOption|null
     */
    private function getOption($inputKey)
    {
        $option = null;

        foreach ($this->getOptions() as $currentOption) {
            $option = $currentOption->getName() == $inputKey ? $currentOption : $option;
        }

        return $option;
    }
}
