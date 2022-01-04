<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute;

use Smile\ElasticsuiteCatalogRule\Api\Rule\Condition\Product\SpecialAttributeInterface;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product as ProductCondition;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Special "is_discount" attribute class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class IsDiscount implements SpecialAttributeInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $booleanSource;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    protected $queryFactory;

    /**
     * IsDiscount constructor.
     *
     * @param \Magento\Config\Model\Config\Source\Yesno                 $booleanSource   Boolean Source.
     * @param \Magento\Customer\Model\Session                           $customerSession Customer session.
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory    Query factory.
     */
    public function __construct(
        \Magento\Config\Model\Config\Source\Yesno $booleanSource,
        \Magento\Customer\Model\Session $customerSession,
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory
    ) {
        $this->booleanSource    = $booleanSource;
        $this->customerSession  = $customerSession;
        $this->queryFactory     = $queryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeCode()
    {
        return 'price.is_discount';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSearchQuery(ProductCondition $condition)
    {
        /*
         * Query cannot be computed directly with the attribute code and value (price.is_discount = true).
         * The customer group needs to be taken into account because the discounted price could be a tiers price.
         */
        return $this->queryFactory->create(
            QueryInterface::TYPE_NESTED,
            [
                'path' => 'price',
                'query' => $this->queryFactory->create(
                    QueryInterface::TYPE_BOOL,
                    [
                        'must' => [
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'price.customer_group_id', 'value' => $this->customerSession->getCustomerGroupId()]
                            ),
                            $this->queryFactory->create(
                                QueryInterface::TYPE_TERM,
                                ['field' => 'price.is_discount', 'value' => true]
                            ),
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOperatorName()
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValueName($value)
    {
        return ' ';
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getValue($value)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueOptions()
    {
        return $this->booleanSource->toOptionArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Only discounted products');
    }
}
