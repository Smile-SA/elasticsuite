<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Setup;

use Smile\ElasticsuiteCore\Setup\ConfigOptionsList as EsConfigOptionsList;

/**
 * ConfigOptionsList Test class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ConfigOptionsListTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Smile\ElasticSuiteCore\Setup\ConfigOptionsList
     */
    private $configOptionsList;

    /**
     * @var \Magento\Framework\App\DeploymentConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deploymentConfigMock;

    /**
     * @var \Smile\ElasticSuiteCore\Client\ClientFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientFactoryMock;

    /**
     * Initialize test components.
     */
    protected function setUp()
    {
        $this->deploymentConfigMock = $this->createMock(\Magento\Framework\App\DeploymentConfig::class);
        $this->clientFactoryMock    = $this->getClientFactory();
        $clientMock                 = $this->getClient();
        $this->clientFactoryMock->method('create')->will($this->returnValue($clientMock));

        $this->configOptionsList    = new EsConfigOptionsList($this->clientFactoryMock);
    }

    /**
     * Data provider for createConfig() testing.
     *
     * Tuples with ['command line options', 'expected configuration written in env.php'].
     *
     * @return array
     */
    public function getCreateConfigDataProvider()
    {
        return [
            [ // Empty command line params should lead to empty config.
                [],
                [],
            ],
            [ // Case when only es-hosts param is provided.
                [
                    'es-hosts' => 'myelasticsearch:9200',
                ],
                [
                    'system' => [
                        'default' => [
                            'smile_elasticsuite_core_base_settings' => [
                                'es_client' => [
                                    'servers' => 'myelasticsearch:9200',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [ // Hosts + Auth.
                [
                    'es-hosts' => 'myelasticsearch:9200',
                    'es-user'  => 'user',
                    'es-pass'  => 'password',
                ],
                [
                    'system' => [
                        'default' => [
                            'smile_elasticsuite_core_base_settings' => [
                                'es_client' => [
                                    'servers'          => 'myelasticsearch:9200',
                                    'http_auth_user'   => 'user',
                                    'http_auth_pwd'    => 'password',
                                    'enable_http_auth' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [ // Hosts + SSL.
                [
                    'es-hosts'      => 'myelasticsearch:9200',
                    'es-enable-ssl' => '1',
                ],
                [
                    'system' => [
                        'default' => [
                            'smile_elasticsuite_core_base_settings' => [
                                'es_client' => [
                                    'servers'           => 'myelasticsearch:9200',
                                    'enable_https_mode' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [ // Hosts + Auth + SSL.
                [
                    'es-hosts'      => 'myelasticsearch:9200',
                    'es-user'       => 'user',
                    'es-pass'       => 'password',
                    'es-enable-ssl' => '1',
                ],
                [
                    'system' => [
                        'default' => [
                            'smile_elasticsuite_core_base_settings' => [
                                'es_client' => [
                                    'servers'           => 'myelasticsearch:9200',
                                    'http_auth_user'    => 'user',
                                    'http_auth_pwd'     => 'password',
                                    'enable_http_auth'  => true,
                                    'enable_https_mode' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [ // Missing Host.
                [
                    'es-user'       => 'user',
                    'es-pass'       => 'password',
                    'es-enable-ssl' => '1',
                ],
                [],
            ],
        ];
    }

    /**
     * Data provider for validate() testing.
     *
     * An array of various combination of command line options.
     *
     * @return array
     */
    public function getValidateDataProvider()
    {
        return [
            [
                [
                    'es-hosts' => 'myelasticsearch:9200',
                ],
            ],
            [
                [
                    'es-hosts' => 'myelasticsearch:9200',
                    'es-user'  => 'user',
                    'es-pass'  => 'password',
                ],
            ],
            [
                [
                    'es-hosts'      => 'myelasticsearch:9200',
                    'es-enable-ssl' => '1',
                ],
            ],
            [
                [
                    'es-hosts'      => 'myelasticsearch:9200',
                    'es-user'       => 'user',
                    'es-pass'       => 'password',
                    'es-enable-ssl' => '1',
                ],
            ],
        ];
    }

    /**
     * Test the getOptions() method.
     */
    public function testGetOptions()
    {
        $options = $this->configOptionsList->getOptions();
        $this->assertCount(4, $options);

        $this->assertArrayHasKey(0, $options);
        $this->assertInstanceOf(\Magento\Framework\Setup\Option\TextConfigOption::class, $options[0]);
        $this->assertEquals('es-hosts', $options[0]->getName());

        $this->assertArrayHasKey(1, $options);
        $this->assertInstanceOf(\Magento\Framework\Setup\Option\FlagConfigOption::class, $options[1]);
        $this->assertEquals('es-enable-ssl', $options[1]->getName());

        $this->assertArrayHasKey(2, $options);
        $this->assertInstanceOf(\Magento\Framework\Setup\Option\TextConfigOption::class, $options[2]);
        $this->assertEquals('es-user', $options[2]->getName());

        $this->assertArrayHasKey(3, $options);
        $this->assertInstanceOf(\Magento\Framework\Setup\Option\TextConfigOption::class, $options[3]);
        $this->assertEquals('es-pass', $options[3]->getName());
    }

    /**
     * @dataProvider getCreateConfigDataProvider
     *
     * @param array $options                  Command Line options
     * @param array $expectedDeploymentConfig Expected deployment config to be written.
     */
    public function testCreateConfig($options, $expectedDeploymentConfig)
    {
        $this->deploymentConfigMock->method('get')->willReturn('');

        $configData = $this->configOptionsList->createConfig($options, $this->deploymentConfigMock);

        $this->assertEquals($expectedDeploymentConfig, $configData[0]->getData());
    }

    /**
     * @dataProvider getValidateDataProvider
     *
     * @param array $options Command Line options
     */
    public function testValidateWithReachableEs($options)
    {
        $errors = $this->configOptionsList->validate($options, $this->deploymentConfigMock);

        $this->assertEmpty($errors);
    }

    /**
     * @dataProvider getValidateDataProvider
     *
     * @param array $options Command Line options
     */
    public function testValidateWithUnReachableEs($options)
    {
        $clientFactory = $this->getClientFactory();
        $clientMock    = $this->getClient();

        $clientMock->method('info')->willThrowException(new \Exception("Exception Message"));
        $clientFactory->method('create')->will($this->returnValue($clientMock));

        $configOptionsList = new EsConfigOptionsList($clientFactory);
        $errors = $configOptionsList->validate($options, $this->deploymentConfigMock);

        $this->assertNotEmpty($errors);
        $this->assertContains("Unable to connect ElasticSearch server : Exception Message", $errors);
    }

    /**
     * Test valid option will succeed
     */
    public function testValidateWithEmptyInput()
    {
        $options = [];
        $errors  = $this->configOptionsList->validate($options, $this->deploymentConfigMock);
        $this->assertEmpty($errors);
    }

    /**
     * Test if validate method is working when using empty option, even if ES is unreachable.
     * Empty options should pass validate even if ES is unreachable.
     */
    public function testValidateWithEmptyInputAndUnreachableES()
    {
        $options       = [];
        $clientFactory = $this->getClientFactory();
        $clientMock    = $this->getClient();
        $clientMock->method('info')->willThrowException(new \Exception("Exception Message"));
        $clientFactory->method('create')->will($this->returnValue($clientMock));

        $configOptionsList = new EsConfigOptionsList($clientFactory);
        $errors = $configOptionsList->validate($options, $this->deploymentConfigMock);

        $this->assertEmpty($errors);
    }

    /**
     * Mocks the client factory.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Smile\ElasticsuiteCore\Client\ClientFactory
     */
    private function getClientFactory()
    {
        $clientFactory = $this->getMockBuilder('\Smile\ElasticsuiteCore\Client\ClientFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        return $clientFactory;
    }

    /**
     * Mocks the ES client.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Smile\ElasticsuiteCore\Client\Client
     */
    private function getClient()
    {
        return $this->createPartialMock(\Smile\ElasticsuiteCore\Client\Client::class, ['info']);
    }
}
