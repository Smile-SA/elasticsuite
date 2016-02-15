<?php

namespace Smile\ElasticSuiteCore\Test\Unit\Client;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Smile\ElasticSuiteCore\Test\Unit\ClientConfiguration;
use Psr\Log\NullLogger as Logger;

class ClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Smile\ElasticSuiteCore\Api\Client\ClientFactoryInterface
     */
    private $clientFactory;

    protected function setUp()
    {
        $om = new ObjectManager($this);
        $this->clientFactory = $om->getObject(
            'Smile\ElasticSuiteCore\Client\ClientFactory',
            [new ClientConfiguration(), new Logger()]
        );
    }

    public function testReturnType()
    {
        $client = $this->clientFactory->createClient();
        $this->assertInstanceOf('\Elasticsearch\Client', $client);
    }
}
