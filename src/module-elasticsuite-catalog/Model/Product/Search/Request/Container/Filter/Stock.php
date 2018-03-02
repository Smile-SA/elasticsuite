<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Product\Search\Request\Container\Filter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Product Stock Default filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Stock implements FilterInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfiguration;

    /**
     * Visibility filter constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory       Query Factory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface        $scopeConfiguration scope Configuration Interface
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
    ) {
        $this->queryFactory       = $queryFactory;
        $this->scopeConfiguration = $scopeConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery(\Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext)
    {
        $query = null;

        if (false === $this->isEnabledShowOutOfStock($searchContext->getStoreId())) {
            $query = $this->queryFactory->create(QueryInterface::TYPE_TERM, ['field' => 'stock.is_in_stock', 'value' => true]);
        }

        return $query;
    }

    /**
     * Get config value for 'display out of stock' option
     *
     * @param int $storeId The Store Id
     *
     * @return bool
     */
    private function isEnabledShowOutOfStock($storeId = null)
    {
        return $this->scopeConfiguration->isSetFlag(
            'cataloginventory/options/show_out_of_stock',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
