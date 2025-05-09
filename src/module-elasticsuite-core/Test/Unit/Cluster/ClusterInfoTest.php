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
        $clientMock = $this->getMockClient('1.0.0');

        $clusterInfo = new ClusterInfo($clientMock);

        $this->assertEquals('1.0.0', $clusterInfo->getServerVersion());
    }

    /**
     * Test retrieving the server distributoin.
     *
     * @return void
     */
    public function testGetServerDistribution()
    {
        $clientMock = $this->getMockClient('1.0.0', 'RandomDistribution');

        $clusterInfo = new ClusterInfo($clientMock);
        $this->assertEquals('RandomDistribution', $clusterInfo->getServerDistribution());

        $clientMock = $this->getMockClient('1.2.3');

        $clusterInfo = new ClusterInfo($clientMock);
        $this->assertEquals(ClusterInfo::DISTRO_ES, $clusterInfo->getServerDistribution());
    }

    /**
     * Return a mock of the client.
     *
     * @param string      $versionNumber Version number.
     * @param string|null $distribution  Distribution (Elasticsearch, OpenSearch, ...)
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockClient($versionNumber, $distribution = null)
    {
        $clientMock = $this->getMockBuilder(\Smile\ElasticsuiteCore\Api\Client\ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $info = ['version' => ['number' => $versionNumber]];
        if ($distribution) {
            $info['version']['distribution'] = $distribution;
        }

        $clientMock->method('info')->will($this->returnValue($info));

        return $clientMock;
    }
}
