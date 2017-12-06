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
namespace Smile\ElasticsuiteCore\Test\Unit\Cluster;

use Smile\ElasticsuiteCore\Cluster\ClusterInfo;
use Smile\ElasticsuiteCore\Api\Client\ClientFactoryInterface;

/**
 * Cluster information tests.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ClusterInfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test retrieving the server version.
     *
     * @return void
     */
    public function testGetServerVersion()
    {
        $client = $this->getMockBuilder(\Elasticsearch\Client::class)->disableOriginalConstructor()->getMock();
        $client->method('info')->will($this->returnValue(['version' => ['number' => '1.0.0']]));

        $clientFactoryMock = $this->getMockBuilder(ClientFactoryInterface::class)->getMock();
        $clientFactoryMock->method('createClient')->will($this->returnValue($client));

        $clusterInfo = new ClusterInfo($clientFactoryMock);

        $this->assertEquals('1.0.0', $clusterInfo->getServerVersion());
    }
}
