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

namespace Smile\ElasticsuiteCatalog\Model\Category\Filter;

use Magento\Catalog\Api\Data\CategoryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Category Filter Provider
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
     * Provider constructor.
     *
     * @param QueryFactory $queryFactory Query Factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * Get condition value (Eg : to be used with addFieldToFilter on fulltext collection)
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return int|QueryInterface
     */
    public function getConditionValue(CategoryInterface $category)
    {
        return $category->getId();
    }

    /**
     * Get query filter (Eg : to be used with addQueryFilter on fulltext collection)
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Category
     *
     * @return QueryInterface
     */
    public function getQueryFilter(CategoryInterface $category)
    {
        $queryParams       = ['field' => 'category.category_id', 'value' => $category->getId()];
        $query             = $this->queryFactory->create(QueryInterface::TYPE_TERM, $queryParams);
        $nestedQueryParams = ['query' => $query, 'path' => 'category'];

        return $this->queryFactory->create(QueryInterface::TYPE_NESTED, $nestedQueryParams);
    }
}
