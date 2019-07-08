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
namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Combine product search rule conditions.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Combine extends \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Combine
{
    /**
     * @var string
     */
    protected $type = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine';

    /**
     * Build a search query for the current rule.
     *
     * @param array    $excludedCategories  Categories excluded of query building (avoid infinite recursion).
     * @param int|null $virtualCategoryRoot Category root for Virtual Category.
     *
     * @return QueryInterface
     */
    public function getSearchQuery($excludedCategories = [], $virtualCategoryRoot = null): QueryInterface
    {
        $queryParams = [];

        $aggregator = $this->getAggregator();
        $value      = (bool) $this->getValue();

        $queryClause = $aggregator === 'all' ? 'must' : 'should';

        foreach ($this->getConditions() as $condition) {
            $subQuery = $condition->getSearchQuery($excludedCategories, $virtualCategoryRoot);
            if (!empty($subQuery) && $subQuery instanceof QueryInterface) {
                if ($value === false) {
                    $subQuery = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $subQuery]);
                }

                $queryParams[$queryClause][] = $subQuery;
            }
        }

        $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);

        return $query;
    }
}
