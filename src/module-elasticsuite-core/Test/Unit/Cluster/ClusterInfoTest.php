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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Cluster;

use Smile\ElasticsuiteCore\Cluster\ClusterInfo;

/**
 * Cluster information tests.
 *
 * @category  Smile
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
        $clientMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Client\ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientMock->method('info')
            ->will($this->returnValue(['version' => ['number' => '1.0.0']]));

        $clusterInfo = new ClusterInfo($clientMock);

        $this->assertEquals('1.0.0', $clusterInfo->getServerVersion());
    }
}
