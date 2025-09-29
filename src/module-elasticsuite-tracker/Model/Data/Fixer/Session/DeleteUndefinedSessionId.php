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

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer\Session;

use Smile\ElasticsuiteCore\Api\Client\ClientInterface;
use Smile\ElasticsuiteCore\Api\Index\IndexSettingsInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteTracker\Api\SessionIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data fixer for undefined session ids in sessions.
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
    public function fixInvalidData(int $storeId): int
    {
        $result = DataFixerInterface::FIX_COMPLETE;

        try {
            $indexAlias = $this->indexSettings->getIndexAliasFromIdentifier(
                SessionIndexInterface::INDEX_IDENTIFIER,
                $storeId
            );

            $query = $this->queryBuilder->buildQuery(
                $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'session_id', 'value' => 'null'])
            );

            $indicesNames = $this->client->getIndicesNameByAlias($indexAlias);
            foreach ($indicesNames as $indexName) {
                $this->client->deleteByQuery([
                    'index' => $indexName,
                    // 'type' => '_doc',
                    'body' => ['query' => $query],
                ]);
            }
        } catch (\Exception $e) {
            $result = DataFixerInterface::FIX_FAILURE;
        }

        return $result;
    }
}
