<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder as QueryBuilder;

/**
 * Build Elasticsearch sort orders from search request specification interface.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param QueryBuilder $queryBuilder Query builder used to build queries inside sort orders.
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Build sort orders.
     *
     * @param SortOrderInterface[] $sortOrders Sort orders specification.
     *
     * @return array
     */
    public function buildSortOrders(array $sortOrders = [])
    {
        return array_map([$this, 'buildSortOrder'], $sortOrders);
    }

    /**
     * Build a sort order ES condition from a SortOrderInterface specification.
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param SortOrderInterface $sortOrder Request sort order specification object.
     *
     * @return array
     */
    private function buildSortOrder(SortOrderInterface $sortOrder)
    {
        $sortField = $sortOrder->getField();

        $sortOrderConfig = [
            'order'         => $sortOrder->getDirection(),
            'missing'       => $sortOrder->getDirection() == SortOrderInterface::SORT_ASC ? '_last' : '_first',
            'unmapped_type' => FieldInterface::FIELD_TYPE_STRING,
        ];

        if ($sortOrder->getType() == SortOrderInterface::TYPE_NESTED) {
            $sortOrderConfig['nested_path']   = $sortOrder->getNestedPath();
            $sortOrderConfig['mode']          = $sortOrder->getScoreMode();

            if ($sortOrder->getNestedFilter()) {
                $sortOrderConfig['nested_filter'] = $this->queryBuilder->buildQuery($sortOrder->getNestedFilter());
            }
        }

        return [$sortField => $sortOrderConfig];
    }
}
