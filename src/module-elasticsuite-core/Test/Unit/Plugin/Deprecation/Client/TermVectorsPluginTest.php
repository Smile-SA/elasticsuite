<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Plugin\Deprecation\Client;

use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Cluster\ClusterInfoInterface;
use Smile\ElasticsuiteCore\Plugin\Deprecation\Client\TermVectorsPlugin;

/**
 * Multi term vectors document type backward compatibility plugin test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class TermVectorsPluginTest extends TestCase
{
    /**
     * Test the document type is added for Elasticsearch < 7.
     *
     * @return void
     */
    public function testDocumentTypeIsAdded()
    {
        $plugin = new TermVectorsPlugin($this->getClusterInfoMock(ClusterInfoInterface::DISTRO_ES, '6.8.23'));
        $result = $plugin->beforeMtermvectors($this->getClientMock(), $this->getParams());

        foreach ($result[0]['body']['docs'] as $doc) {
            $this->assertEquals('_doc', $doc['_type']);
        }
    }

    /**
     * Test the document type is not added for other servers.
     *
     * @dataProvider serverWithoutDocumentTypeDataProvider
     *
     * @param string $distribution Server distribution.
     * @param string $version      Server version.
     *
     * @return void
     */
    public function testDocumentTypeIsNotAdded($distribution, $version)
    {
        $params = $this->getParams();
        $plugin = new TermVectorsPlugin($this->getClusterInfoMock($distribution, $version));
        $result = $plugin->beforeMtermvectors($this->getClientMock(), $params);

        $this->assertEquals([$params], $result);
        foreach ($result[0]['body']['docs'] as $doc) {
            $this->assertArrayNotHasKey('_type', $doc);
        }
    }

    /**
     * Server distributions and versions not using a document type.
     *
     * @return array
     */
    public function serverWithoutDocumentTypeDataProvider()
    {
        return [
            'Elasticsearch 7' => [ClusterInfoInterface::DISTRO_ES, '7.17.0'],
            'OpenSearch 1'    => [ClusterInfoInterface::DISTRO_OS, '1.2.4'],
            'OpenSearch 2'    => [ClusterInfoInterface::DISTRO_OS, '2.19.2'],
        ];
    }

    /**
     * Test params without docs are left untouched.
     *
     * @return void
     */
    public function testParamsWithoutDocs()
    {
        $params = ['index' => 'my_index', 'body' => ['ids' => ['1', '2']]];
        $plugin = new TermVectorsPlugin($this->getClusterInfoMock(ClusterInfoInterface::DISTRO_ES, '6.8.23'));

        $this->assertEquals([$params], $plugin->beforeMtermvectors($this->getClientMock(), $params));
    }

    /**
     * Test an existing document type is kept.
     *
     * @return void
     */
    public function testExistingDocumentTypeIsKept()
    {
        $params = $this->getParams();
        $params['body']['docs'][0]['_type'] = 'doc';
        $plugin = new TermVectorsPlugin($this->getClusterInfoMock(ClusterInfoInterface::DISTRO_ES, '6.8.23'));
        $result = $plugin->beforeMtermvectors($this->getClientMock(), $params);

        $this->assertEquals('doc', $result[0]['body']['docs'][0]['_type']);
        $this->assertEquals('_doc', $result[0]['body']['docs'][1]['_type']);
    }

    /**
     * Test the server is only queried once.
     *
     * @return void
     */
    public function testServerInfoIsMemoized()
    {
        $clusterInfo = $this->getMockBuilder(ClusterInfoInterface::class)->getMock();
        $clusterInfo->expects($this->once())
            ->method('getServerDistribution')
            ->willReturn(ClusterInfoInterface::DISTRO_ES);
        $clusterInfo->expects($this->once())->method('getServerVersion')->willReturn('6.8.23');

        $plugin = new TermVectorsPlugin($clusterInfo);
        $plugin->beforeMtermvectors($this->getClientMock(), $this->getParams());
        $plugin->beforeMtermvectors($this->getClientMock(), $this->getParams());
    }

    /**
     * Multi term vectors params, as built by the spellchecker.
     *
     * @return array
     */
    private function getParams()
    {
        $doc = [
            '_index'          => 'my_index',
            'term_statistics' => true,
            'fields'          => ['spelling'],
            'doc'             => ['spelling' => 'shrit'],
        ];

        return [
            'body' => [
                'docs' => [
                    array_merge($doc, ['routing' => '[my_index][0]']),
                    array_merge($doc, ['routing' => '[my_index][1]']),
                ],
            ],
        ];
    }

    /**
     * Cluster information mocking.
     *
     * @param string $distribution Server distribution.
     * @param string $version      Server version.
     *
     * @return ClusterInfoInterface
     */
    private function getClusterInfoMock($distribution, $version)
    {
        $clusterInfo = $this->getMockBuilder(ClusterInfoInterface::class)->getMock();
        $clusterInfo->method('getServerDistribution')->willReturn($distribution);
        $clusterInfo->method('getServerVersion')->willReturn($version);

        return $clusterInfo;
    }

    /**
     * Client mocking.
     *
     * @return ClientInterface
     */
    private function getClientMock()
    {
        return $this->getMockBuilder(ClientInterface::class)->getMock();
    }
}
