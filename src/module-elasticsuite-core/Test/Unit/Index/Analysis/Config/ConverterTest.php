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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Analysis\Config;

use Smile\ElasticsuiteCore\Index\Analysis\Config\Converter;

/**
 * Index configuration filte converter test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    private $parsedData;

    protected function setUp()
    {
        $xml = new \DOMDocument();
        $xml->load(__DIR__ . '/elasticsuite_analysis.xml');
        $converter = new Converter(new \Magento\Framework\Json\Decoder());
        $this->parsedData = $converter->convert($xml);
    }

    public function testAvailableLanguages()
    {
        $this->assertCount(5, $this->parsedData);
        $this->assertArrayHasKey('default', $this->parsedData);
        $this->assertArrayHasKey('override_language', $this->parsedData);

        $this->assertArrayHasKey('filter_generated_language', $this->parsedData);
        $this->assertArrayHasKey('analyzer_generated_language', $this->parsedData);
    }

    public function testCharFilters()
    {
        $defaultCharFilters = $this->parsedData['default']['char_filter'];

        $this->assertArrayHasKey('char_filter', $defaultCharFilters);
        $charFilter = $defaultCharFilters['char_filter'];
        $this->assertEquals('char_filter_type', $charFilter['type']);

        $charFilter = $defaultCharFilters['char_filter_with_params'];
        $this->assertArrayHasKey('char_filter_with_params', $defaultCharFilters);
        $this->assertEquals('char_filter_with_params_type', $charFilter['type']);

        $this->assertEquals('paramValue', $charFilter['simpleParam']);
        $this->assertEquals('value', $charFilter['jsonParamObject']['key']);
        $this->assertCount(2, $charFilter['jsonParamArray']);
    }

    public function testLanguageCharFilters()
    {
        $this->assertArrayHasKey('char_filter_generated_language', $this->parsedData);
        $charFilterGeneratedLanguages = $this->parsedData['char_filter_generated_language']['char_filter'];
        $this->assertEquals('dummy', $charFilterGeneratedLanguages['dummy']['type']);

        $charFilterOverrides = $this->parsedData['override_language']['char_filter'];
        $this->assertEquals('char_filter_type_language_override', $charFilterOverrides['char_filter']['type']);
    }

    public function testFilters()
    {
        $defaultFilters = $this->parsedData['default']['filter'];

        $this->assertArrayHasKey('filter', $defaultFilters);
        $filter = $defaultFilters['filter'];
        $this->assertEquals('filter_type', $filter['type']);

        $filter = $defaultFilters['filter_with_params'];
        $this->assertArrayHasKey('filter_with_params', $defaultFilters);
        $this->assertEquals('filter_with_params_type', $filter['type']);

        $this->assertEquals('paramValue', $filter['simpleParam']);
        $this->assertEquals('value', $filter['jsonParamObject']['key']);
        $this->assertCount(2, $filter['jsonParamArray']);
    }

    public function testLanguageFilters()
    {
        $this->assertArrayHasKey('filter_generated_language', $this->parsedData);
        $filterGeneratedLanguages = $this->parsedData['filter_generated_language']['filter'];
        $this->assertEquals('dummy', $filterGeneratedLanguages['dummy']['type']);

        $filterOverrides = $this->parsedData['override_language']['filter'];
        $this->assertEquals('filter_type_language_override', $filterOverrides['filter']['type']);
    }

    public function testAnalyzers()
    {
        $defaultAnalyzers = $this->parsedData['default']['analyzer'];
        $this->assertCount(1, $defaultAnalyzers);

        $this->assertArrayHasKey('analyzer', $defaultAnalyzers);
        $this->assertEquals('tokenizer', $defaultAnalyzers['analyzer']['tokenizer']);
        $this->assertEquals('custom', $defaultAnalyzers['analyzer']['type']);

        $this->assertContains('char_filter', $defaultAnalyzers['analyzer']['char_filter']);
        $this->assertContains('char_filter_with_params', $defaultAnalyzers['analyzer']['char_filter']);

        $this->assertContains('filter', $defaultAnalyzers['analyzer']['filter']);
        $this->assertContains('filter_with_params', $defaultAnalyzers['analyzer']['filter']);
    }
}
