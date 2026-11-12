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

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Helper\Cache;
use Smile\ElasticsuiteCore\Helper\IndexSettings;
use Smile\ElasticsuiteCore\Helper\Text;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusCacheConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfig;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusConfigFactory;
use Smile\ElasticsuiteThesaurus\Config\ThesaurusStemmingConfig;
use Smile\ElasticsuiteThesaurus\Model\Index as ThesaurusIndex;

/**
 * Thesaurus index unit tests with stemming, against a fake analysis engine.
 * Rules, stored stemmed as the index handler does: "port_bougi,bougeoir", "chais,sieg", "tabl,bureau", "dress,gown".
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 */
class IndexStemmingTest extends TestCase
{
    /**
     * @var string[] Names of the analyzers called, in order.
     */
    private $calledAnalyzers = [];

    /**
     * Test rewrites when stemming is enabled.
     *
     * @param string $queryText         Initial query text.
     * @param array  $expectedAnalyzers Expected analyzers called, in order.
     * @param array  $expectedRewrites  Rewrites that must be present (query => weight).
     *
     * @return void
     */
    #[DataProvider('stemmedRewritesDataProvider')]
    public function testStemmedRewrites($queryText, $expectedAnalyzers, $expectedRewrites)
    {
        $rewrites = $this->getThesaurusIndex(true)->getQueryRewrites($this->getContainerConfig(), $queryText);

        $this->assertEquals($expectedAnalyzers, $this->calledAnalyzers);
        foreach ($expectedRewrites as $rewrite => $weight) {
            $this->assertArrayHasKey($rewrite, $rewrites);
            $this->assertEqualsWithDelta($weight, $rewrites[$rewrite], 0.0001);
        }
    }

    /**
     * Data provider for testStemmedRewrites.
     *
     * @return array
     */
    public static function stemmedRewritesDataProvider()
    {
        return [
            'one word: stem only, no shingles (they need two words)' => [
                'dresses',
                ['clean', 'synonym', 'synonym'],
                ['gown' => 0.1],
            ],
            'multi-word rule: the stemmed shingle matches the stemmed rule' => [
                'porte bougies',
                ['clean', 'shingles', 'synonym', 'synonym', 'synonym', 'synonym'],
                ['bougeoir' => 0.1],
            ],
            'two one-word rules in the same query: each word is stemmed on its own' => [
                'chaises tables',
                ['clean', 'shingles', 'synonym', 'synonym', 'synonym', 'synonym'],
                ['sieg tables' => 0.1, 'chaises bureau' => 0.1],
            ],
        ];
    }

    /**
     * Test that the "clean" analyzer is never called when stemming is disabled.
     *
     * @return void
     */
    public function testNoStemAnalysisWithoutStemming()
    {
        $thesaurusIndex = $this->getThesaurusIndex(false);

        $thesaurusIndex->getQueryRewrites($this->getContainerConfig(), 'porte bougies');
        $this->assertEquals(['shingles', 'synonym', 'synonym'], $this->calledAnalyzers);

        $this->calledAnalyzers = [];
        $thesaurusIndex->getQueryRewrites($this->getContainerConfig(), 'dresses');
        $this->assertEquals(['synonym'], $this->calledAnalyzers);
    }

    /**
     * Build a thesaurus index whose client runs the fake analysis engine.
     *
     * @param bool $useStemming Whether stemming is enabled.
     *
     * @return ThesaurusIndex
     */
    private function getThesaurusIndex($useStemming)
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('analyze')->willReturnCallback(
            function ($params) {
                $this->calledAnalyzers[] = $params['body']['analyzer'];

                return ['tokens' => $this->fakeAnalyze($params['body']['analyzer'], $params['body']['text'])];
            }
        );

        $indexSettingsHelper = $this->createStub(IndexSettings::class);
        $indexSettingsHelper->method('getIndexAliasFromIdentifier')->willReturn('magento2_default_thesaurus');

        $cacheHelper = $this->createStub(Cache::class);
        $cacheHelper->method('loadCache')->willReturn(false);

        $thesaurusConfig = $this->createStub(ThesaurusConfig::class);
        $thesaurusConfig->method('isSynonymSearchEnabled')->willReturn(true);
        $thesaurusConfig->method('getSynonymWeightDivider')->willReturn(10);
        $thesaurusConfig->method('isExpansionSearchEnabled')->willReturn(false);
        $thesaurusConfig->method('getMaxRewrites')->willReturn(2);
        $thesaurusConfig->method('getMaxRewrittenQueries')->willReturn(0);

        $thesaurusConfigFactory = $this->createStub(ThesaurusConfigFactory::class);
        $thesaurusConfigFactory->method('create')->willReturn($thesaurusConfig);

        $stemmingConfig = $this->createStub(ThesaurusStemmingConfig::class);
        $stemmingConfig->method('useStemming')->willReturn($useStemming);

        return new ThesaurusIndex(
            $client,
            $indexSettingsHelper,
            $cacheHelper,
            new Text(),
            $thesaurusConfigFactory,
            $this->createStub(ThesaurusCacheConfig::class),
            $stemmingConfig
        );
    }

    /**
     * Container configuration.
     *
     * @return ContainerConfigurationInterface
     */
    private function getContainerConfig()
    {
        $containerConfig = $this->createStub(ContainerConfigurationInterface::class);
        $containerConfig->method('getStoreId')->willReturn(1);
        $containerConfig->method('getName')->willReturn('requestType');

        return $containerConfig;
    }

    /**
     * Fake analysis engine.
     *
     * @param string $analyzer Analyzer name (clean, shingles or synonym).
     * @param string $text     Text to analyze.
     *
     * @return array
     */
    private function fakeAnalyze($analyzer, $text)
    {
        if ($analyzer === 'synonym') {
            return $this->fakeSynonyms($this->fakeTokenize($text, false));
        }

        $words = $this->fakeTokenize($text, true);

        return $analyzer === 'shingles' ? $this->fakeShingles($words) : $words;
    }

    /**
     * Whitespace tokenizer and lowercase, then optionally a stemmer that strips a final "s", then a final "e".
     *
     * @param string $text Text to analyze.
     * @param bool   $stem Whether to stem the words.
     *
     * @return array
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function fakeTokenize($text, $stem)
    {
        $words = [];
        preg_match_all('/\S+/u', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$word, $byteOffset]) {
            $start = mb_strlen(substr($text, 0, $byteOffset));
            $token = mb_strtolower($word);
            if ($stem) {
                $token = preg_replace('/e$/', '', preg_replace('/s$/', '', $token));
            }
            $words[] = ['token' => $token, 'start_offset' => $start, 'end_offset' => $start + mb_strlen($word)];
        }

        return $words;
    }

    /**
     * Two-word shingles joined with "_".
     *
     * @param array $words Tokens.
     *
     * @return array
     */
    private function fakeShingles($words)
    {
        $shingles = [];
        $previous = null;

        foreach ($words as $word) {
            if ($previous !== null) {
                $shingles[] = [
                    'token'        => $previous['token'] . '_' . $word['token'],
                    'start_offset' => $previous['start_offset'],
                    'end_offset'   => $word['end_offset'],
                ];
            }
            $previous = $word;
        }

        return $shingles;
    }

    /**
     * Synonym filter on the stemmed rules.
     *
     * @param array $words Tokens.
     *
     * @return array
     */
    private function fakeSynonyms($words)
    {
        $rules    = [['port_bougi', 'bougeoir'], ['chais', 'sieg'], ['tabl', 'bureau'], ['dress', 'gown']];
        $synonyms = [];

        foreach ($words as $word) {
            foreach ($rules as $rule) {
                if (!in_array($word['token'], $rule, true)) {
                    continue;
                }
                foreach (array_diff($rule, [$word['token']]) as $synonym) {
                    $synonyms[] = ['type' => 'SYNONYM', 'token' => $synonym] + $word;
                }
            }
        }

        return $synonyms;
    }
}
