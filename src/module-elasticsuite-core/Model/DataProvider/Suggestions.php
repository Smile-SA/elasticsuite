<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\DataProvider;

use Magento\AdvancedSearch\Model\SuggestedQueriesInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Search\Model\QueryInterface;
use Magento\Search\Model\QueryResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Search\Request\ContainerConfigurationFactory;

/**
 * Elasticsuite Search Suggestions.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class Suggestions implements SuggestedQueriesInterface
{
    /**
     * Max number of suggestions.
     */
    const MAX_COUNT = 3;

    /**
     * @var QueryResultFactory
     */
    private QueryResultFactory $queryResultFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Client\ClientInterface
     */
    private ClientInterface $client;

    /**
     * @var ContainerConfigurationFactory
     */
    private ContainerConfigurationFactory $containerConfigFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * DataProvider constructor.
     *
     * @param QueryResultFactory            $queryResultFactory     Query result factory.
     * @param StoreManagerInterface         $storeManager           Store Manager.
     * @param ClientInterface               $client                 Elasticsearch client.
     * @param ContainerConfigurationFactory $containerConfigFactory Container Config Factory
     * @param ScopeConfigInterface          $scopeConfig            Scope Configuration
     */
    public function __construct(
        QueryResultFactory $queryResultFactory,
        StoreManagerInterface $storeManager,
        ClientInterface $client,
        ContainerConfigurationFactory $containerConfigFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->queryResultFactory     = $queryResultFactory;
        $this->storeManager           = $storeManager;
        $this->client                 = $client;
        $this->containerConfigFactory = $containerConfigFactory;
        $this->scopeConfig            = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function isResultsCountEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(QueryInterface $query)
    {
        $suggestions = [];
        if ($this->isSuggestionsAllowed()) {
            foreach ($this->getSuggestions($query->getQueryText(), $this->getMaxSize()) as $suggestion) {
                $suggestions[] = $this->queryResultFactory->create(
                    [
                        'queryText' => $suggestion['text'],
                        'resultsCount' => 0,
                    ]
                );
            }
        }

        return $suggestions;
    }

    /**
     * Fetch suggestions from Elasticsearch with a "suggest" query.
     *
     * @param string $queryText The query text
     * @param int    $maxSize   The maximum number of items to fetch
     *
     * @return array
     */
    private function getSuggestions(string $queryText, int $maxSize = self::MAX_COUNT)
    {
        $suggestions     = [];
        $containerConfig = $this->getRequestContainerConfiguration(
            $this->storeManager->getStore()->getId(),
            'quick_search_container'
        );

        $phraseSuggestField = MappingInterface::DEFAULT_SEARCH_FIELD . '.' . FieldInterface::ANALYZER_WHITESPACE;

        $suggestRequest = [
            'index' => $containerConfig->getIndexName(),
            'body' => [
                'suggest' => [
                    'text' => $queryText,
                    'phrase_suggestions' => [ // This key is a named object.
                        'phrase' => [
                            'field' => $phraseSuggestField,
                            'size'  => $maxSize,
                            'direct_generator' => [
                                [
                                    'field'           => $phraseSuggestField,
                                    'suggest_mode'    => 'always',
                                    'min_word_length' => 1,
                                ],
                            ],
                            'collate' => [
                                'query'  => ['source' => ['match' => ['{{field_name}}' => '{{suggestion}}']]],
                                'params' => ['field_name' => $phraseSuggestField],
                                'prune'  => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->client->search($suggestRequest);

        if (is_array($result)) {
            foreach ($result['suggest']['phrase_suggestions'] ?? [] as $token) {
                foreach ($token['options'] as $key => $suggestion) {
                    $suggestions[$suggestion['score'] . '_' . $key] = $suggestion;
                }
            }
            ksort($suggestions);
            $suggestions = array_slice($suggestions, 0, $maxSize);
        }

        return $suggestions;
    }

    /**
     * Load the search request configuration (index, type, mapping, ...) using the search request container name.
     *
     * @throws \LogicException Thrown when the search container is not found into the configuration.
     *
     * @param integer $storeId       Store id.
     * @param string  $containerName Search request container name.
     *
     * @return ContainerConfigurationInterface
     */
    private function getRequestContainerConfiguration($storeId, $containerName)
    {
        if ($containerName === null) {
            throw new \LogicException('Request name is not set');
        }

        $config = $this->containerConfigFactory->create(
            ['containerName' => $containerName, 'storeId' => $storeId]
        );

        if ($config === null) {
            throw new \LogicException("No configuration exists for request {$containerName}");
        }

        return $config;
    }

    /**
     * Get Max size of suggestions to display.
     *
     * @return int
     */
    private function getMaxSize(): int
    {
        return (int) $this->scopeConfig->getValue(self::SEARCH_SUGGESTION_COUNT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is Search Suggestions Allowed
     *
     * @return bool
     */
    private function isSuggestionsAllowed()
    {
        return $this->scopeConfig->isSetFlag(
            self::SEARCH_SUGGESTION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }
}
