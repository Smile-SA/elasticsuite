<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\Aggregation;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Attribute\AggregationInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Price aggregation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Price implements AggregationInterface
{
    /**
     * XML configuration paths for Price Layered Navigation
     */
    const XML_PATH_RANGE_CALCULATION = 'catalog/layered_navigation/price_range_calculation';
    const XML_PATH_RANGE_STEP        = 'catalog/layered_navigation/price_range_step';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * Price constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig     Scope Config
     * @param \Magento\Customer\Model\Session                    $customerSession Customer session, if any
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregationData(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        $bucketConfig = [
            'name'         => 'price.price',
            'type'         => BucketInterface::TYPE_HISTOGRAM,
            'nestedFilter' => ['price.customer_group_id' => $customerGroupId], 'minDocCount' => 1,
        ];

        $calculation = $this->getRangeCalculationValue();
        if ($calculation === \Magento\Catalog\Model\Layer\Filter\DataProvider\Price::RANGE_CALCULATION_MANUAL) {
            if ((int) $this->getRangeStepValue() > 0) {
                $bucketConfig['interval'] = (int) $this->getRangeStepValue();
            }
        }

        return $bucketConfig;
    }

    /**
     * @return mixed
     */
    private function getRangeCalculationValue()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RANGE_CALCULATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    private function getRangeStepValue()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RANGE_STEP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
