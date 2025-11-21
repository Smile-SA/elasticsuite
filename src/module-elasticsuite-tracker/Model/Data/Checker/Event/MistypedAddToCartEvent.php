<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Data\Checker\Event;

use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\ElasticsuiteTracker\Api\EventIndexInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\AbstractDataChecker;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckResultFactory;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data checker for mistyped checkout_cart_add events where the page type identifier
 * is located in page.identifier instead of page.type.identifier.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
class MistypedAddToCartEvent extends AbstractDataChecker implements DataCheckerInterface
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
        return sprintf("%d add to cart events not correctly typed.", $docCount);
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchRequest($storeId): RequestInterface
    {
        // The correct location for the page identifier is page.type.identifier.
        $queryFilters = [
            $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                [
                    'must' => [
                        $this->queryFactory->create(
                            QueryInterface::TYPE_MISSING,
                            ['field' => 'page.type.identifier']
                        ),
                        $this->queryFactory->create(
                            QueryInterface::TYPE_TERM,
                            ['field' => 'page.identifier', 'value' => 'checkout_cart_add']
                        ),
                    ],
                ]
            ),
        ];

        return $this->searchRequestBuilder->create($storeId, EventIndexInterface::INDEX_IDENTIFIER, 0, 0, null, [], [], $queryFilters);
    }
}
