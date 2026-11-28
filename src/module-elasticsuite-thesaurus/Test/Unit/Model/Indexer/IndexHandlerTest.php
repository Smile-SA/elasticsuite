<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Model\Indexer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexOperationInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Helper\Cache as CacheHelper;
use Smile\ElasticsuiteCore\Helper\IndexSettings as IndexSettingsHelper;
use Smile\ElasticsuiteCore\Helper\Text as TextHelper;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusStemmingConfig;
use Smile\ElasticsuiteThesaurus\Model\Indexer\IndexHandler;

/**
 * Thesaurus index handler unit tests: stemming of the thesaurus rows.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 */
class IndexHandlerTest extends TestCase
{
    /**
     * @var array Parameters of every analyze call.
     */
    private $analyzeCalls = [];

    /**
     * @var array Settings sent to createIndex.
     */
    private $createdSettings = [];

    /**
     * Test the synonym rows stored in the index, with and without stemming.
     *
     * @param bool     $useStemming   Whether stemming is enabled.
     * @param string[] $synonyms      Raw synonym rows.
     * @param string[] $expectedRows  Expected rows in the synonym filter.
     * @param int      $expectedCalls Expected number of analyze calls.
     *
     * @return void
     */
    #[DataProvider('synonymRowsDataProvider')]
    public function testSynonymRows($useStemming, $synonyms, $expectedRows, $expectedCalls)
    {
        $this->getIndexHandler($useStemming)->reindex(1, $synonyms, []);

        $this->assertEquals($expectedRows, $this->createdSettings['analysis']['filter']['synonym']['synonyms']);
        $this->assertCount($expectedCalls, $this->analyzeCalls);
    }

    /**
     * Data provider for testSynonymRows.
     *
     * @return array
     */
    public static function synonymRowsDataProvider()
    {
        return [
            'no stemming: legacy rows, no analyze call' => [
                false,
                ['porte bougie,bougeoir', 'portes-bougies,bougeoir'],
                ['porte_bougie,bougeoir', 'portes_bougies,bougeoir'],
                0,
            ],
            'multi-word rule stemmed word by word, one call for all rows' => [
                true,
                ['porte bougie,bougeoir', 'chaises,sieges'],
                ['port_bougi,bougeoir', 'chais,sieg'],
                1,
            ],
            'hyphen split like the query' => [
                true,
                ['portes-bougies,bougeoir'],
                ['port_bougi,bougeoir'],
                1,
            ],
            'multibyte offsets and a word dropped by a filter' => [
                true,
                ['Tables à manger,tables de cuisines'],
                ['tabl_a_manger,tabl_de_cuisin'],
                1,
            ],
            'stem longer than the word, keyword_repeat: the word then its stem, the stem is kept' => [
                true,
                ['pot,jarre'],
                ['pottery,jarr'],
                1,
            ],
            'a character outside the BMP (UTF-16 offsets)' => [
                true,
                ['cierges 🕯,bougeoirs', 'chaises,sieges'],
                ['cierg_🕯,bougeoir', 'chais,sieg'],
                1,
            ],
        ];
    }

    /**
     * Test that the expansion rows keep their "=>" separator.
     *
     * @return void
     */
    public function testExpansionRows()
    {
        $this->getIndexHandler(true)->reindex(1, [], ['sports=>footballs,tennis']);

        $this->assertEquals(
            ['sport=>football,tenni'],
            $this->createdSettings['analysis']['filter']['expansion']['synonyms']
        );
    }

    /**
     * Test that the rows are split into several analyze calls above the word limit.
     *
     * @return void
     */
    public function testAnalyzeBatches()
    {
        $row = trim(str_repeat('mot ', 2000)) . ',synonyme';

        $this->getIndexHandler(true)->reindex(1, [$row, $row, $row], []);

        $this->assertCount(2, $this->analyzeCalls);
        $this->assertCount(3, $this->createdSettings['analysis']['filter']['synonym']['synonyms']);
    }

    /**
     * Test that an analysis failure keeps the rows unstemmed and still builds the index.
     *
     * @return void
     */
    public function testAnalyzeFailure()
    {
        $exception = new \RuntimeException('403 no permissions for [indices:admin/analyze]');
        $logger    = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('403 no permissions'));
        $this->getIndexHandler(true, null, $exception, $logger)->reindex(1, ['porte bougie,bougeoir'], []);

        $this->assertEquals(
            ['porte_bougie,bougeoir'],
            $this->createdSettings['analysis']['filter']['synonym']['synonyms']
        );
    }

    /**
     * Test the index targeted by the analyze call.
     *
     * @return void
     */
    public function testAnalyzeIndex()
    {
        $this->getIndexHandler(true)->reindex(1, ['chaises,sieges'], []);
        $this->assertArrayNotHasKey('index', $this->analyzeCalls[0]);

        $this->analyzeCalls = [];
        $this->getIndexHandler(true, 'catalog_product')->reindex(1, ['chaises,sieges'], []);
        $this->assertEquals('magento2_1_catalog_product', $this->analyzeCalls[0]['index']);
    }

    /**
     * Build an index handler on a fake analysis engine.
     *
     * @param bool                 $useStemming            Whether stemming is enabled.
     * @param string|null          $analyzeIndexIdentifier Index identifier used for the analyze call.
     * @param \Exception|null      $analyzeException       Exception thrown by the analyze call, if any.
     * @param LoggerInterface|null $logger                 Logger (a stub if null).
     *
     * @return IndexHandler
     */
    private function getIndexHandler($useStemming, $analyzeIndexIdentifier = null, $analyzeException = null, $logger = null)
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('analyze')->willReturnCallback(
            function ($params) use ($analyzeException) {
                $this->analyzeCalls[] = $params;
                if ($analyzeException !== null) {
                    throw $analyzeException;
                }

                return ['tokens' => $this->fakeStemAnalyze($params['body']['text'])];
            }
        );
        $client->method('createIndex')->willReturnCallback(
            function (...$args) {
                $this->createdSettings = $args[1]['settings'];
            }
        );

        $indexSettingsHelper = $this->createStub(IndexSettingsHelper::class);
        $indexSettingsHelper->method('getIndexAliasFromIdentifier')->willReturnCallback(
            function ($identifier, $storeId) {
                return sprintf('magento2_%s_%s', $storeId, $identifier);
            }
        );
        $indexSettingsHelper->method('createIndexNameFromIdentifier')->willReturn('magento2_1_thesaurus_new');
        $indexSettingsHelper->method('getMaxShingleDiff')->willReturn(8);

        $indexSettings = $this->createStub(IndexSettingsInterface::class);
        $indexSettings->method('getAnalysisSettings')->willReturn(
            ['filter' => ['stemmer' => ['type' => 'stemmer', 'language' => 'light_french']]]
        );

        $stemmingConfig = $this->createStub(ThesaurusStemmingConfig::class);
        $stemmingConfig->method('useStemming')->willReturn($useStemming);

        return new IndexHandler(
            $client,
            $this->createStub(IndexOperationInterface::class),
            $indexSettingsHelper,
            $indexSettings,
            $stemmingConfig,
            $this->createStub(CacheHelper::class),
            new TextHelper(),
            $logger ?? $this->createStub(LoggerInterface::class),
            $analyzeIndexIdentifier
        );
    }

    /**
     * Fake stemming analysis with offsets in UTF-16 code units, as Lucene gives them: whitespace tokenizer,
     * lowercase, "à" folded to "a", "de" removed (stop word), "pot" repeated then stemmed to "pottery"
     * (keyword_repeat), then a final "s" and a final "e" stripped.
     *
     * @param string $text Text to analyze.
     *
     * @return array
     */
    private function fakeStemAnalyze($text)
    {
        $tokens = [];
        preg_match_all('/\S+/u', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$word, $byteOffset]) {
            $start  = $this->utf16Length(substr($text, 0, $byteOffset));
            $offset = ['start_offset' => $start, 'end_offset' => $start + $this->utf16Length($word)];
            $token  = str_replace('à', 'a', mb_strtolower($word));

            if ($token === 'de') {
                continue;
            }
            if ($token === 'pot') {
                $tokens[] = ['token' => 'pot'] + $offset;
                $tokens[] = ['token' => 'pottery'] + $offset;
                continue;
            }

            $tokens[] = ['token' => preg_replace('/e$/', '', preg_replace('/s$/', '', $token))] + $offset;
        }

        return $tokens;
    }

    /**
     * Length of a text in UTF-16 code units.
     *
     * @param string $text Text.
     *
     * @return int
     */
    private function utf16Length($text)
    {
        return strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) / 2;
    }
}
