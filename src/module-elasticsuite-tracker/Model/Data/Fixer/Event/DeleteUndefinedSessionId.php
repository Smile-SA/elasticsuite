<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer\Event;

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data fixer for undefined session ids in events.
 * Delete strategy.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DeleteUndefinedSessionId implements DataFixerInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var IndexSettingsInterface
     */
    private $indexSettings;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor.
     *
     * @param QueryFactory           $queryFactory  Query factory.
     * @param QueryBuilder           $queryBuilder  Query Builder.
     * @param IndexSettingsInterface $indexSettings Index settings.
     * @param ClientInterface        $client        Elasticsearch client.
     */
    public function __construct(
        QueryFactory $queryFactory,
        QueryBuilder $queryBuilder,
        IndexSettingsInterface $indexSettings,
        ClientInterface $client
    ) {
        $this->queryFactory = $queryFactory;
        $this->queryBuilder = $queryBuilder;
        $this->indexSettings = $indexSettings;
        $this->client = $client;
    }

    /**
     * {@inheritDoc}
     */
    public function fixInvalidData(int $storeId): bool
    {
        $success = true;

        try {
            $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier(
                EventIndexInterface::INDEX_IDENTIFIER,
                $storeId
            );

            $query = $this->queryBuilder->buildQuery(
                $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'should' => [
                            $this->queryFactory->create(QueryInterface::TYPE_MISSING, ['field' => 'session.uid']),
                            $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'session.uid', 'value' => 'null']),
                        ],
                    ]
                )
            );

            $indicesNames = $this->client->getIndicesNameByAlias($indexAlias);
            foreach ($indicesNames as $indexName) {
                $params = [
                    'index' => $indexName,
                    // 'type' => '_doc',
                    'body' => ['query' => $query],
                ];
                $this->client->deleteByQuery($params);
            }
        } catch (\Exception $e) {
            $success = false;
        }

        return $success;
    }
}
