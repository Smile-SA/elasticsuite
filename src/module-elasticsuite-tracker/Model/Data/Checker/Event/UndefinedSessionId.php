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
use Smile\ElasticsuiteTracker\Model\Data\Checker\AbstractDataChecker;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckResultFactory;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data checker for undefined session ids in events.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class UndefinedSessionId extends AbstractDataChecker implements DataCheckerInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

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
        $this->queryFactory         = $queryFactory;
        parent::__construct($checkResultFactory, $searchRequestBuilder, $searchEngine, $dataFixer);
    }

    /**
     * {@inheritDoc}
     */
    protected function getInvalidDocsDescription(int $docCount): string
    {
        return sprintf("%d events without any defined session id.", $docCount);
    }

    /**
     * Build search request used to find event docs with a non-defined or invalid session identifier.
     *
     * @param int $storeId Store id.
     *
     * @return RequestInterface
     */
    protected function getSearchRequest($storeId): RequestInterface
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
