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
namespace Smile\ElasticsuiteAnalytics\Model\Report\Session;

use Smile\ElasticsuiteAnalytics\Model\Report\QueryProviderInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteAnalytics\Model\Report\Context;

/**
 * Customer company id filter query provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
class CustomerCompanyIdFilterQueryProvider implements QueryProviderInterface
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
     * CustomerCompanyIdFilterQueryProvider constructor.
     *
     * @param QueryFactory $queryFactory Query factory.
     * @param Context      $context      Report context.
     */
    public function __construct(QueryFactory $queryFactory, Context $context)
    {
        $this->queryFactory = $queryFactory;
        $this->context = $context;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery()
    {
        // Get customer company ID from the context.
        $customerCompanyId = $this->context->getCustomerCompanyId();

        // Check if customer company ID is set and not 'all'.
        if ($customerCompanyId !== 'all' && $customerCompanyId !== null) {
            // Return a TERM query for the customer company ID.
            return $this->queryFactory->create(
                QueryInterface::TYPE_BOOL,
                [
                    'must' => [
                        $this->queryFactory->create(
                            QueryInterface::TYPE_TERM,
                            [
                                'field' => 'customer_company_id',
                                'value' => (int) $customerCompanyId,
                            ]
                        ),
                    ],
                ]
            );
        }

        // If 'all' is selected or no customer company ID is set, return null (no filtering).
        return null;
    }
}
