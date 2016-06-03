<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteVirtualCategory\Model\Rule\Condition;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Combine product search rule conditions.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Combine extends \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Combine
{
    /**
     * @var string
     */
    protected $type = 'Smile\ElasticSuiteVirtualCategory\Model\Rule\Condition\Combine';

    /**
     * Build a search query for the current rule.
     *
     * @param array $excludedCategories Categories excluded of query building (avoid infinite recursion).
     *
     * @return QueryInterface
     */
    public function getSearchQuery($excludedCategories = [])
    {
        $queryParams = [];

        $aggregator = $this->getAggregator();
        $value      = (bool) $this->getValue();

        $queryClause = $aggregator === 'all' ? 'must' : 'should';

        foreach ($this->getConditions() as $condition) {
            $subQuery = $condition->getSearchQuery($excludedCategories);
            if ($subQuery !== null && $subQuery instanceof QueryInterface) {
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
