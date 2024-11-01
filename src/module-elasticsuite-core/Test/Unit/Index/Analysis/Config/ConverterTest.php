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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Analysis\Config;

use Smile\ElasticsuiteCore\Index\Analysis\Config\Converter;

/**
 * Analysis configuration file converter test case.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    private $parsedData;

    /**
     * Test available language list is OK for the sample test file.
     *
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $xml = new \DOMDocument();
        $xml->load(__DIR__ . '/elasticsuite_analysis.xml');
        $converter = new Converter(new \Magento\Framework\Json\Decoder());
        $this->parsedData = $converter->convert($xml);
    }

    /**
     * Test available language list is OK for the sample test file.
     *
     * @return void
     */
    public function testAvailableLanguages()
    {
        $this->assertCount(7, $this->parsedData);
        $this->assertArrayHasKey('default', $this->parsedData);
        $this->assertArrayHasKey('override_language', $this->parsedData);

        $this->assertArrayHasKey('char_filter_generated_language', $this->parsedData);
        $this->assertArrayHasKey('filter_generated_language', $this->parsedData);
        $this->assertArrayHasKey('analyzer_generated_language', $this->parsedData);

        $this->assertArrayHasKey('language_without_stemmer', $this->parsedData);
        $this->assertArrayHasKey('language_with_stemmer', $this->parsedData);
    }

    /**
     * Test available char filters for the default language in the sample test file.
     *
     * @return void
     */
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

    /**
     * Test available char filters by language in the sample test file.
     *
     * @return void
     */
    public function testLanguageCharFilters()
    {
        $this->assertArrayHasKey('char_filter_generated_language', $this->parsedData);
        $charFilterGeneratedLanguages = $this->parsedData['char_filter_generated_language']['char_filter'];
        $this->assertEquals('dummy', $charFilterGeneratedLanguages['dummy']['type']);

        $charFilterOverrides = $this->parsedData['override_language']['char_filter'];
        $this->assertEquals('char_filter_type_language_override', $charFilterOverrides['char_filter']['type']);
    }

    /**
     * Test available filters for the default language in the sample test file.
     *
     * @return void
     */
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

    /**
     * Test available filters by language in the sample test file.
     *
     * @return void
     */
    public function testLanguageFilters()
    {
        $this->assertArrayHasKey('filter_generated_language', $this->parsedData);
        $filterGeneratedLanguages = $this->parsedData['filter_generated_language']['filter'];
        $this->assertEquals('dummy', $filterGeneratedLanguages['dummy']['type']);

        $filterOverrides = $this->parsedData['override_language']['filter'];
        $this->assertEquals('filter_type_language_override', $filterOverrides['filter']['type']);
    }

    /**
     * Test analyzers for the default language in the sample test file.
     *
     * @return void
     */
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

    /**
     * Test analyzers with or without a stemmer depending on their availability in a given language.
     *
     * @return void
     */
    public function testStemmerPresence()
    {
        $this->assertArrayHasKey('language_without_stemmer', $this->parsedData);
        $this->assertArrayHasKey('analyzer', $this->parsedData['language_without_stemmer']);
        $this->assertArrayHasKey('stemmed_analyzer', $this->parsedData['language_without_stemmer']['analyzer']);

        $stemmedAnalyzer = $this->parsedData['language_without_stemmer']['analyzer']['stemmed_analyzer'];
        $this->assertArrayHasKey('filter', $stemmedAnalyzer);
        $this->assertNotContains('stemmer', $stemmedAnalyzer['filter']);

        $this->assertArrayHasKey('language_with_stemmer', $this->parsedData);
        $this->assertArrayHasKey('analyzer', $this->parsedData['language_with_stemmer']);
        $this->assertArrayHasKey('stemmed_analyzer', $this->parsedData['language_with_stemmer']['analyzer']);

        $stemmedAnalyzer = $this->parsedData['language_with_stemmer']['analyzer']['stemmed_analyzer'];
        $this->assertArrayHasKey('filter', $stemmedAnalyzer);
        $this->assertContains('stemmer', $stemmedAnalyzer['filter']);
    }
}
