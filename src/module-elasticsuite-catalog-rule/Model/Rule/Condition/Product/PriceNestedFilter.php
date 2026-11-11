<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\ContextAwareInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\QueryContext\CustomerGroupProvider;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Restricts the conditions on the prices (eg. "Price is less than 100") to the prices of the current customer group.
 *
 * The prices are indexed per customer group: without this filter, a condition would match a product as soon as the
 * price of any customer group matches.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class PriceNestedFilter implements NestedFilterInterface, ContextAwareInterface
{
    /**
     * Field of the customer group of the prices.
     */
    const FILTER_FIELD = 'price.customer_group_id';

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var QueryContextResolverInterface
     */
    private $queryContextResolver;

    /**
     * Constructor.
     *
     * @param QueryFactory                  $queryFactory         Query factory.
     * @param QueryContextResolverInterface $queryContextResolver Values of the contexts rule queries can depend on.
     */
    public function __construct(QueryFactory $queryFactory, QueryContextResolverInterface $queryContextResolver)
    {
        $this->queryFactory         = $queryFactory;
        $this->queryContextResolver = $queryContextResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilter()
    {
        // The customer group is read the same way as when the queries depending on it are cached.
        $values = $this->queryContextResolver->getValues([CustomerGroupProvider::CODE]);

        return $this->queryFactory->create(
            QueryInterface::TYPE_TERM,
            ['field' => self::FILTER_FIELD, 'value' => (int) $values[CustomerGroupProvider::CODE]]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getContextDependencies(): array
    {
        return [CustomerGroupProvider::CODE];
    }
}
