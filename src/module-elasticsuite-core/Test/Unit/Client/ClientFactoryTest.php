<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Client;

use \Smile\ElasticsuiteCore\Client\ClientFactory;

/**
 * ES client factory test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Testing execution of the client creation with default params.
     *
     * @return void
     */
    public function testCreateClientDefault()
    {
        $clientConfiguration = $this->getClientConfigurationMock();
        $clientConfiguration->method('isHttpAuthEnabled')->will($this->returnValue(false));

        $clientFactory = $this->getClientFactory($this->getClientConfigurationMock());

        $this->assertInstanceOf(\Elasticsearch\Client::class, $clientFactory->createClient());
    }

    /**
     * Testing execution of the client creation with auth enabled.
     *
     * @return void
     */
    public function testCreateClientWithAuthentication()
    {
        $clientConfiguration = $this->getClientConfigurationMock();

        $clientConfiguration->method('isHttpAuthEnabled')->will($this->returnValue(true));
        $clientConfiguration->method('getHttpAuthUser')->will($this->returnValue('user'));
        $clientConfiguration->method('getHttpAuthPassword')->will($this->returnValue('password'));

        $clientFactory = $this->getClientFactory($clientConfiguration);

        $this->assertInstanceOf(\Elasticsearch\Client::class, $clientFactory->createClient());
    }

    /**
     * Testing execution of the client creation with debug mode enabled.
     *
     * @return void
     */
    public function testCreateClientWithDebigModeEnabled()
    {
        $clientConfiguration = $this->getClientConfigurationMock();

        $clientConfiguration->method('isDebugModeEnabled')->will($this->returnValue(true));

        $clientFactory = $this->getClientFactory($clientConfiguration);

        $this->assertInstanceOf(\Elasticsearch\Client::class, $clientFactory->createClient());
    }

    /**
     * Create a new client factory.
     *
     * @param \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface $clientConfiguration Client configuration.
     *
     * @return \Smile\ElasticsuiteCore\Client\ClientFactory
     */
    private function getClientFactory($clientConfiguration)
    {
        $clientBuilderFactory = $this->getClientBuilderMock();
        $logger               = $this->getLoggerMock();

        return new ClientFactory($clientBuilderFactory, $clientConfiguration, $logger);
    }

    /**
     * ES client builder mock.
     *
     * @return \Elasticsearch\ClientBuilder
     */
    private function getClientBuilderMock()
    {
        $clientBuilder = $this->getMockBuilder(\Elasticsearch\ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client = $this->getMockBuilder(\Elasticsearch\Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientBuilder->method('build')
            ->will($this->returnValue($client));

        return $clientBuilder;
    }

    /**
     * Client configuration mock.
     *
     * @return \Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface
     */
    private function getClientConfigurationMock()
    {
        $clientConfiguration = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Client\ClientConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientConfiguration->method('getServerList')
            ->will($this->returnValue(['localhost:9200']));

        $clientConfiguration->method('getScheme')
            ->will($this->returnValue('http'));

        return $clientConfiguration;
    }

    /**
     * Logger mock.
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function getLoggerMock()
    {
        $mockBuilder = $this->getMockBuilder(\Psr\Log\LoggerInterface::class);

        return $mockBuilder->disableOriginalConstructor()->getMock();
    }
}
