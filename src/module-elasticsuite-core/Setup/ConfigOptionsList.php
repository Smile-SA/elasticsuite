<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Carey Sizer <carey@balanceinternet.com.au>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\Option\TextConfigOption;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;
use Smile\ElasticsuiteCore\Helper\ClientConfiguration;
use Smile\ElasticsuiteCore\Client\ClientFactoryFactory;
use Smile\ElasticsuiteCore\Setup\ClientConfigurationFactory;

/**
 * Provides configuration options to the Magento setup command for Elasticsuite.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Carey Sizer <carey@balanceinternet.com.au>
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Input key for the options
     */
    const INPUT_KEY_ES_SERVER = 'es-server';

    /**
     * Input key for the options
     */
    const INPUT_KEY_ES_SERVER_HTTP_AUTH_USER = 'es-server-http-auth-user';

    /**
     * Input key for the options
     */
    const INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD = 'es-server-http-auth-password';

    /**
     * Default ES server variable
     */
    const DEFAULT_ES_SERVER = 'localhost:9200';

    /**
     * @var ClientFactoryFactory
     */
    private $clientFactoryFactory;

    /**
     * @var ClientConfigurationFactory
     */
    private $clientConfigurationFactory;

    /**
     * ConfigOptionsList constructor.
     *
     * @param ClientFactoryFactory $clientFactoryFactory
     * @param ClientConfigurationFactory $clientConfigurationFactory
     */
    public function __construct(
        ClientFactoryFactory $clientFactoryFactory,
        ClientConfigurationFactory $clientConfigurationFactory
    ) {
        $this->clientFactoryFactory = $clientFactoryFactory;
        $this->clientConfigurationFactory = $clientConfigurationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return [
            new TextConfigOption(
                self::INPUT_KEY_ES_SERVER,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                ClientConfiguration::CONFIG_PATH_CLIENT_SERVERS,
                'Elasticsuite server(s)',
                self::DEFAULT_ES_SERVER
            ),
            new TextConfigOption(
                self::INPUT_KEY_ES_SERVER_HTTP_AUTH_USER,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                ClientConfiguration::CONFIG_PATH_HTTP_AUTH_USER,
                'Elasticsuite server(s) HTTP Auth User'
            ),
            new TextConfigOption(
                self::INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                ClientConfiguration::CONFIG_PATH_HTTP_AUTH_PWD,
                'Elasticsuite server(s) HTTP Auth Pass'
            ),
        ];
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createConfig(array $data, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);
        $configPrefix = 'system/default/';

        // Apply the provided server configuration if it is set and not an empty string
        if (!$this->isDataEmpty($data, self::INPUT_KEY_ES_SERVER)) {
            $configData->set(
                $configPrefix . ClientConfiguration::CONFIG_PATH_CLIENT_SERVERS,
                $data[self::INPUT_KEY_ES_SERVER]
            );
        }

        // If the user has supplied a username or password, assume they are using auth
        if (!$this->isDataEmpty($data, self::INPUT_KEY_ES_SERVER_HTTP_AUTH_USER) ||
            !$this->isDataEmpty($data, self::INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD)) {
            $httpAuthConfig = [
                ClientConfiguration::CONFIG_PATH_ENABLE_HTTP_AUTH => 1,
                ClientConfiguration::CONFIG_PATH_HTTP_AUTH_USER => $data[self::INPUT_KEY_ES_SERVER_HTTP_AUTH_USER],
                ClientConfiguration::CONFIG_PATH_HTTP_AUTH_PWD => $data[self::INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD],
            ];
            foreach ($httpAuthConfig as $configKey => $configValue) {
                $configData->set($configPrefix . $configKey, $configKey);
            }
        }

        return [$configData];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        if (!$this->isDataEmpty($options, self::INPUT_KEY_ES_SERVER)) {
            /** @var ClientFactoryInterface $clientFactory */
            $clientFactory = $this->clientFactoryFactory->create([
                'clientConfiguration' => $this->createClientConfiguration($options),
            ]);

            try {
                if (!$clientFactory->createClient()->ping()) {
                    $errors[] = "Could not ping the supplied Elasticsuite server.";
                }
            } catch (\Exception $ex) {
                $errors[] = "Elasticsuite server error: " . $ex->getMessage();
            }
        }

        return $errors;
    }

    /**
     * @param array $options
     * @return \Smile\ElasticsuiteCore\Setup\ClientConfiguration
     */
    private function createClientConfiguration(array $options)
    {
        /** @var  $clientConfiguration */
        $configurationArgs = [
            'serverList' => $options[self::INPUT_KEY_ES_SERVER],
        ];

        // If HTTP Auth is enabled, add those configurations
        if ($this->isHttpAuthEnabled($options)) {
            $configurationArgs = array_merge($configurationArgs, [
                'isHttpAuthEnabled' => true,
                'httpAuthUser'      => $options[self::INPUT_KEY_ES_SERVER_HTTP_AUTH_USER] ?? null,
                'httpAuthPassword'  => $options[self::INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD] ?? null,
            ]);
        }

        return $this->clientConfigurationFactory->create($configurationArgs);
    }

    /**
     * Check if HTTP Auth is required to be enabled.
     *
     * @param $data
     * @return bool
     */
    private function isHttpAuthEnabled(array $data)
    {
        foreach ([self::INPUT_KEY_ES_SERVER_HTTP_AUTH_USER, self::INPUT_KEY_ES_SERVER_HTTP_AUTH_PWD] as $possibleKey) {
            if (!$this->isDataEmpty($data, $possibleKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if data ($data) with key ($key) is empty
     *
     * @param array $data
     * @param string $key
     * @return bool
     */
    private function isDataEmpty(array $data, $key)
    {
        if (isset($data[$key]) && $data[$key] !== '') {
            return false;
        }

        return true;
    }
}
