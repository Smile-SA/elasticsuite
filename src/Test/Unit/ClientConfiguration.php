<?php

namespace Smile\ElasticSuiteCore\Test\Unit;


use Smile\ElasticSuiteCore\Api\Client\ClientConfigurationInterface;

class ClientConfiguration implements ClientConfigurationInterface
{
    private $serverList = ['localhost:9200'];

    private $connectionTimeout = 1;

    public function getServerList()
    {
        return $this->serverList;
    }

    public function isDebugModeEnabled()
    {
        return true;
    }

    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }
}
