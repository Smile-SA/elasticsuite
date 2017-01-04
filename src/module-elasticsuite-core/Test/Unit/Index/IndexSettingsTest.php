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
namespace Smile\ElasticsuiteCore\Test\Unit\Index;

use Smile\ElasticsuiteCore\Index\IndexSettings;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Index\Analysis\Config as AnalysisConfig;
use Smile\ElasticsuiteCore\Index\Indices\Config as IndicesConfig;

/**
 * Index settings test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexSettingsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IndexSettings
     */
    private $indexSettings;

    protected function setUp()
    {
        $indexSettingHelper = $this->getIndexSettingsMock();
        $indicesConfig      = $this->getIndicesConfigMock();
        $analysisConfig     = $this->getAnalysisConfigMock();

        $this->indexSettings = new IndexSettings($indexSettingHelper, $indicesConfig, $analysisConfig);
    }

    private function getIndexSettingsMock()
    {
        $mockBuilder        = $this->getMockBuilder(IndexSettingsHelper::class);
        $indexSettingHelper = $mockBuilder->disableOriginalConstructor()->getMock();

        $methodStub = $this->returnCallback(function ($indexIdentifier, $store) { return "{$indexIdentifier}_{$store}"; });
        $indexSettingHelper->method('getIndexAliasFromIdentifier')->will($methodStub);
        $indexSettingHelper->method('createIndexNameFromIdentifier')->will($methodStub);
        $indexSettingHelper->method('getBatchIndexingSize')->will($this->returnValue(100));
        $indexSettingHelper->method('getNumberOfShards')->will($this->returnValue(1));
        $indexSettingHelper->method('getNumberOfReplicas')->will($this->returnValue(1));
        $indexSettingHelper->method('getLanguageCode')->will(
            $this->returnCallback(function ($store) { return "language_{$store}"; })
        );

        return $indexSettingHelper;
    }

    private function getIndicesConfigMock()
    {
        $mockBuilder  = $this->getMockBuilder(IndicesConfig::class);
        $indicesConfig = $mockBuilder->disableOriginalConstructor()->getMock();
        $indicesConfig->method('get')->will($this->returnValue(['index' => 'indexConfiguration']));

        return $indicesConfig;
    }

    private function getAnalysisConfigMock()
    {
        $mockBuilder    = $this->getMockBuilder(AnalysisConfig::class);
        $analysisConfig = $mockBuilder->disableOriginalConstructor()->getMock();
        $analysisConfig->method('get')->will(
            $this->returnCallback(function ($languageCode) { return "analysis_{$languageCode}"; })
        );

        return $analysisConfig;
    }

    public function testGetIndexAliasFromIdentifier()
    {
        $alias = $this->indexSettings->getIndexAliasFromIdentifier('index_identifier', 'store_code');
        $this->assertEquals("index_identifier_store_code", $alias);
    }

    public function testCreateIndexNameFromIdentifier()
    {
        $indexName = $this->indexSettings->createIndexNameFromIdentifier('index_identifier', 'store_code');
        $this->assertEquals("index_identifier_store_code", $indexName);
    }

    public function testGetBatchIndexingSize()
    {
        $this->assertEquals(100, $this->indexSettings->getBatchIndexingSize());
    }

    public function testGetIndexConfig()
    {
        $this->assertEquals('indexConfiguration', $this->indexSettings->getIndexConfig('index'));
    }

    /**
     * Test an exception is raised when accessing an index that does not exists in the configuration.
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage No indices found with identifier invalidIndex
     *
     * @return void
     */
    public function testGetInvalidIndexConfig()
    {
        $this->indexSettings->getIndexConfig('invalidIndex');
    }

    public function testGetAnalysisSettings()
    {
        $config = $this->indexSettings->getAnalysisSettings('store_code');
        $this->assertEquals('analysis_language_store_code', $config);
    }

    public function testIndexingSettings()
    {
        $createIndexSettings  = $this->indexSettings->getCreateIndexSettings();
        $installIndexSettings = $this->indexSettings->getInstallIndexSettings();
        $this->assertEquals(1, $createIndexSettings['number_of_shards']);
        $this->assertEquals(0, $createIndexSettings['number_of_replicas']);
        $this->assertEquals(1, $installIndexSettings['number_of_replicas']);
    }
}
