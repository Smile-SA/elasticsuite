<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\Option\FlagConfigOption;

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
     * Constructor.
     *
     * @param \Smile\ElasticsuiteCore\Client\ClientBuilder $clientBuilder ES client builder.
     */
    public function __construct(\Smile\ElasticsuiteCore\Client\ClientBuilder $clientBuilder)
    {
        $this->clientBuilder = $clientBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return [
            new TextConfigOption(
                self::INPUT_KEY_ES_HOSTS,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_ES_HOSTS,
                'ElasticSearch Servers List.'
            ),
            new FlagConfigOption(
                self::INPUT_KEY_ES_SSL,
                self::CONFIG_PATH_ES_SSL,
                'Use SSL mode to connect to ElasticSearch.'
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
        ];
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
            $errors[] = "Unable to connect ElasticSearch server : {$e->getMessage()}";
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
        $clientOptions = [];

        if (isset($options[self::INPUT_KEY_ES_HOSTS])) {
            $clientOptions = [
                'servers'           => $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_HOSTS),
                'enable_https_mode' => $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_SSL),
                'http_auth_user'    => (string) $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_USER),
                'http_auth_pwd'     => (string) $this->readConfiguration($options, $deploymentConfig, self::INPUT_KEY_ES_PASS),
            ];

            $clientOptions['enable_http_auth'] = !empty($clientOptions['http_auth_user']) && !empty($clientOptions['http_auth_pwd']);
        }

        return $clientOptions;
    }

    /**
     * Read a value from the input with fallback to the current deployment config.
     *
     * @param array            $options          Input options.
     * @param DeploymentConfig $deploymentConfig Deployment config.
     * @param unknown          $inputKey         Name of the variable in the input options.
     *
     * @return mixed
     */
    private function readConfiguration(array $options, DeploymentConfig $deploymentConfig, $inputKey)
    {
        $configPath = $this->getConfigPath($inputKey);

        return $options[$inputKey] ?? ($configPath != null ? $deploymentConfig->get($configPath) : null);
    }

    /**
     * Convert an inout key to a config path.
     *
     * @param string $inputKey Input key.
     *
     * @return NULL|string
     */
    private function getConfigPath($inputKey)
    {
        $configPath = null;

        foreach ($this->getOptions() as $option) {
            $configPath = $option->getName() == $inputKey ? $option->getConfigPath() : $configPath;
        }

        return $configPath;
    }
}
