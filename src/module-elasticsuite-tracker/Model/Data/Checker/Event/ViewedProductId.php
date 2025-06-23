<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

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
 * Behavioral data checker for product view events when a product id of 0,
 * which can happen if the product uid (base64 encoded product id) was sent instead of the product id.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class ViewedProductId extends AbstractDataChecker implements DataCheckerInterface
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
        return sprintf("%d fixable product view events with a product id of '0'", $docCount);
    }

    /**
     * {@inheritDoc}
     */
    protected function getSearchRequest($storeId): RequestInterface
    {
        $queryFilters = [
            $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                [
                    'must' => [
                        $this->queryFactory->create(
                            QueryInterface::TYPE_TERM,
                            ['field' => 'page.type.identifier', 'value' => 'catalog_product_view']
                        ),
                        $this->queryFactory->create(
                            QueryInterface::TYPE_TERM,
                            ['field' => 'page.product.id', 'value' => 0]
                        ),
                        $this->queryFactory->create(
                            QueryInterface::TYPE_EXISTS,
                            ['field' => 'page.product.sku']
                        ),
                    ],
                ]
            ),
        ];

        return $this->searchRequestBuilder->create($storeId, EventIndexInterface::INDEX_IDENTIFIER, 0, 0, null, [], [], $queryFilters);
    }
}
