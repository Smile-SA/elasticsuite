<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Product\Search\Request\Container\Filter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Category Blacklist filter implementation
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryBlacklist implements FilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * Category Blacklist filter constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory Query Factory
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory
    ) {
        $this->queryFactory       = $queryFactory;
    }

    /**
     * Get filter query according to current search context.
     *
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext Search Context
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    public function getFilterQuery(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext)
    {
        $query = null;
        if ($searchContext->getCurrentCategory()) {
            $query = $this->getIsNotBlacklistedQuery((int) $searchContext->getCurrentCategory()->getId());
        }

        return $query;
    }

    /**
     * Create the "is not blacklisted" query according to context parameters.
     *
     * @param int $value The nested query field id value
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
     */
    private function getIsNotBlacklistedQuery($value)
    {
        $isBlacklisted = $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path'  => 'category',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'category.category_id', 'value' => $value]
                            ),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'category.is_blacklisted', 'value' => true]
                            ),
                        ],
                    ]
                ),
            ]
        );

        $notBlacklisted = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $isBlacklisted]);

        return $notBlacklisted;
    }
}
