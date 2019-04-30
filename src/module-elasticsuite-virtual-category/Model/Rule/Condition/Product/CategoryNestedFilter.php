<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Product;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;

/**
 * Category product nested filter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryNestedFilter implements \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\NestedFilterInterface
{
    /**
     * @var string
     */
    const FILTER_FIELD = 'category.is_virtual';

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param QueryFactory $queryFactory Query factory.
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilter()
    {
        $filterParams = ['field' => self::FILTER_FIELD, 'value' => true];
        $filterQuery  = $this->queryFactory->create(
            QueryInterface::TYPE_NOT,
            ['query' => $this->queryFactory->create(QueryInterface::TYPE_TERM, $filterParams)]
        );

        return $filterQuery;
    }
}
