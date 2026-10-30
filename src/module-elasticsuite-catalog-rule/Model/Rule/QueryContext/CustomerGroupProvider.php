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

namespace Smile\ElasticsuiteCatalogRule\Model\Rule\QueryContext;

use Magento\Customer\Model\Session;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContext\ProviderInterface;

/**
 * Provides the customer group of the current customer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CustomerGroupProvider implements ProviderInterface
{
    /**
     * Context code.
     */
    const CODE = 'customer_group';

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Constructor.
     *
     * @param Session $customerSession Customer session.
     */
    public function __construct(Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return (int) $this->customerSession->getCustomerGroupId();
    }
}
