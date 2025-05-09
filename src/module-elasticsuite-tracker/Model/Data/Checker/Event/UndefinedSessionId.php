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

namespace Smile\ElasticsuiteTracker\Model\Data\Checker\Event;

use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckResult;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckResultFactory;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data checker for undefined session ids in events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class UndefinedSessionId implements DataCheckerInterface
{
    /**
     * @var DataCheckResultFactory
     */
    private $checkResultFactory;

    /**
     * @var Builder
     */
    private $searchRequestBuilder;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var SearchEngineInterface
     */
    private $searchEngine;

    /**
     * @var ?DataFixerInterface
     */
    private $dataFixer;

    /**
     * Constructor.
     *
     * @param DataCheckResultFactory  $checkResultFactory   Data checker result factory.
     * @param Builder                 $searchRequestBuilder Search request builder.
     * @param QueryFactory            $queryFactory         Search query factory.
     * @param SearchEngineInterface   $searchEngine         Search engine.
     * @param DataFixerInterface|null $dataFixer            Invalid data fixer.
     */
    public function __construct(
        DataCheckResultFactory $checkResultFactory,
        Builder $searchRequestBuilder,
        QueryFactory $queryFactory,
        SearchEngineInterface $searchEngine,
        ?DataFixerInterface $dataFixer = null
    ) {
        $this->checkResultFactory   = $checkResultFactory;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->queryFactory         = $queryFactory;
        $this->searchEngine         = $searchEngine;
        $this->dataFixer            = $dataFixer;
    }

    /**
     * Check that no event has the field session.id missing.
     *
     * @param int $storeId Store id.
     *
     * @return DataCheckResult
     */
    public function check($storeId): DataCheckResult
    {
        /** @var DataCheckResult $checkResult */
        $checkResult = $this->checkResultFactory->create([]);

        try {
            $request = $this->getSearchRequest($storeId);
            $response = $this->searchEngine->search($request);
            if ($response->count() > 0) {
                $checkResult->setInvalidData(true);
                $checkResult->setDescription(sprintf("%d events without any defined session id.", $response->count()));
            }
        } catch (\LogicException $e) {
            ;
        }

        return $checkResult;
    }

    /**
     * {@inheritDoc}
     */
    public function hasDataFixer(): bool
    {
        return $this->dataFixer instanceof DataFixerInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function getDataFixer(): ?DataFixerInterface
    {
        return $this->dataFixer;
    }

    /**
     * Build search request used to check invalid event data.
     *
     * @param int $storeId Store id.
     *
     * @return RequestInterface
     */
    private function getSearchRequest($storeId): RequestInterface
    {
        $queryFilters = [
            $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                [
                    'should' => [
                        $this->queryFactory->create(QueryInterface::TYPE_MISSING, ['field' => 'session.uid']),
                        $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'session.uid', 'value' => 'null']),
                    ],
                ]
            ),
        ];

        return $this->searchRequestBuilder->create($storeId, EventIndexInterface::INDEX_IDENTIFIER, 0, 0, null, [], [], $queryFilters);
    }
}
