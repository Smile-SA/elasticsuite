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

namespace Smile\ElasticsuiteCore\Test\Unit\Plugin\Index\IndexSettings;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Plugin\Index\IndexSettings\ShingleTokenizerPlugin;

/**
 * Shingle analyzer tokenizer plugin test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
#[AllowMockObjectsWithoutExpectations]
class ShingleTokenizerPluginTest extends TestCase
{
    /**
     * Test the configured tokenizer is applied to the shingle analyzer.
     *
     * @return void
     */
    public function testStandardTokenizer()
    {
        $result = $this->getPlugin('standard')->afterGetAnalysisSettings(
            $this->getIndexSettingsMock(),
            $this->getAnalysisSettings(),
            1
        );

        $this->assertEquals('standard', $result['analyzer']['shingle']['tokenizer']);
        $this->assertEquals('whitespace', $result['analyzer']['whitespace']['tokenizer']);
    }

    /**
     * Test the legacy tokenizer leaves the shingle analyzer unchanged.
     *
     * @return void
     */
    public function testWhitespaceTokenizer()
    {
        $settings = $this->getAnalysisSettings();
        $result   = $this->getPlugin('whitespace')->afterGetAnalysisSettings($this->getIndexSettingsMock(), $settings, 1);

        $this->assertEquals($settings, $result);
    }

    /**
     * Test settings without shingle analyzer are left untouched.
     *
     * @return void
     */
    public function testMissingShingleAnalyzer()
    {
        $settings = $this->getAnalysisSettings();
        unset($settings['analyzer']['shingle']);
        $result = $this->getPlugin('standard')->afterGetAnalysisSettings($this->getIndexSettingsMock(), $settings, 1);

        $this->assertEquals($settings, $result);
    }

    /**
     * Instantiate the plugin with a mocked scope config returning the given tokenizer.
     *
     * @param string $tokenizer Configured tokenizer.
     *
     * @return ShingleTokenizerPlugin
     */
    private function getPlugin($tokenizer)
    {
        $scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $scopeConfig->method('getValue')
            ->with('smile_elasticsuite_core_analysis_settings/shingle_analyzer/tokenizer')
            ->willReturn($tokenizer);

        return new ShingleTokenizerPlugin($scopeConfig);
    }

    /**
     * Index settings mock.
     *
     * @return IndexSettingsInterface
     */
    private function getIndexSettingsMock()
    {
        return $this->getMockBuilder(IndexSettingsInterface::class)->getMock();
    }

    /**
     * Sample analysis settings.
     *
     * @return array
     */
    private function getAnalysisSettings()
    {
        return [
            'analyzer' => [
                'shingle'    => ['tokenizer' => 'whitespace', 'filter' => ['lowercase', 'shingle']],
                'whitespace' => ['tokenizer' => 'whitespace', 'filter' => ['lowercase']],
            ],
        ];
    }
}
