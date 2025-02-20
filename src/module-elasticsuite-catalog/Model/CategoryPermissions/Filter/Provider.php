<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\CategoryPermissions\Filter;

use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Query Provider for Catalog permissions
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Provider
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory Query Factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Build a clause to filter on products which are not available for the current customer group id.
     * By default, the clause is a "NOT equal to -2" in legacy Magento.s
     *
     * @param int    $customerGroupId Customer Group Id
     * @param int    $value           Permission value
     * @param string $operator        Operator to use (default is mustNot because the legacy query is "is not denied")
     *
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface|null
     */
    public function getQueryFilter(int $customerGroupId, int $value, string $operator = 'mustNot') : ?QueryInterface
    {
        $query = $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path'  => 'category_permissions',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'category_permissions.customer_group_id', 'value' => $customerGroupId]
                            ),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'category_permissions.permission', 'value' => $value]
                            ),
                        ],
                    ]
                ),
            ]
        );

        if ('mustNot' === $operator) {
            $query = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $query]);
        }

        return $query;
    }
}
