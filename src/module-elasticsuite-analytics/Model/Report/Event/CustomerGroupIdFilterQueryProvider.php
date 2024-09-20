<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteAnalytics\Model\Report\Event;

use Smile\ElasticsuiteAnalytics\Model\Report\QueryProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteAnalytics\Model\Report\Context;

/**
 * Customer group id filter query provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CustomerGroupIdFilterQueryProvider implements QueryProviderInterface
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var Context
     */
    private $context;

    /**
     * DateFilterQueryProvider constructor.
     *
     * @param QueryFactory $queryFactory Query factory.
     * @param Context      $context      Report context.
     */
    public function __construct(QueryFactory $queryFactory, Context $context)
    {
        $this->queryFactory = $queryFactory;
        $this->context      = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery()
    {
        // Get customer group ID from the context.
        $customerGroupId = $this->context->getCustomerGroupId();

        // Check if customer group ID is set and not 'all'.
        if ($customerGroupId !== 'all' && $customerGroupId !== null) {
            // Return a TERM query for the customer group ID.
            return $this->queryFactory->create(
                QueryInterface::TYPE_TERM,
                [
                    'field' => 'customer.group_id',
                    'value' => (int) $customerGroupId,
                ]
            );
        }

        // If 'all' is selected or no customer group ID is set, return null (no filtering).
        return null;
    }
}
