<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Product\Search\Request\Container\Filter;

use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

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
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var \Smile\ElasticsuiteCore\Api\Search\ContextInterface
     */
    private $searchContext;

    /**
     * Search Blacklist filter constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory       Query Factory
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface       $searchContext      Current search context.
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration Stock configuration.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
    ) {
            $this->queryFactory       = $queryFactory;
            $this->searchContext      = $searchContext;
            $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery()
    {
        $query = null;

        if (false === $this->isEnabledShowOutOfStock($this->searchContext->getStoreId())) {
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
        return $this->stockConfiguration->isShowOutOfStock($storeId);
    }
}
