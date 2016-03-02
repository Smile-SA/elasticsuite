<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\SortOrder;

use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticSuiteCore\Api\Index\MappingInterface;
use Smile\ElasticSuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticSuiteCore\Search\Request\Query\Filter\QueryBuilder;

/**
 * Allow to build a sort order from arrays.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SortOrderBuilder
{
    /**
     * @var StandardFactory
     */
    private $standardOrderFactory;

    /**
     * @var NestedFactory
     */
    private $nestedOrderFactory;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param StandardFactory $standardOrderFactory Standard sort order factory.
     * @param NestedFactory   $nestedOrderFactory   Nested sort order factory.
     * @param QueryBuilder    $queryBuilder         Query builder used to build queries inside nested sort order.
     */
    public function __construct(
        StandardFactory $standardOrderFactory,
        NestedFactory $nestedOrderFactory,
        QueryBuilder $queryBuilder
    ) {
        $this->standardOrderFactory = $standardOrderFactory;
        $this->nestedOrderFactory   = $nestedOrderFactory;
        $this->queryBuilder         = $queryBuilder;
    }

    /**
     * Build sort orders from array of sort orders definition.
     *
     * @param array $requestConfiguration Request configuration.
     * @param array $orders               Sort orders definitions.
     *
     * @return SortOrderInterface[]
     */
    public function buildSordOrders(array $requestConfiguration, array $orders)
    {
        $sortOrders = [];
        $mapping    = $requestConfiguration['mapping'];

        if (!in_array(SortOrderInterface::DEFAULT_SORT_FIELD, array_keys($orders))) {
            $orders[SortOrderInterface::DEFAULT_SORT_FIELD] = [
                'direction' => SortOrderInterface::DEFAULT_SORT_DIRECTION,
            ];
        }

        foreach ($orders as $fieldName => $sortOrderParams) {
            $factory = $this->standardOrderFactory;

            try {
                $sortField       = $mapping->getField($fieldName);
                $sortOrderParams = $this->getSortOrderParams($sortField, $sortOrderParams);

                if (isset($sortOrderParams['nestedPath'])) {
                    $factory = $this->nestedOrderFactory;
                }

                if (isset($sortOrderParams['nestedFilter'])) {
                    $nestedFilter = $this->queryBuilder->create($mapping, $sortOrderParams['nestedFilter']);
                    $sortOrderParams['nestedFilter'] = $nestedFilter->getQuery();
                }
            } catch (\LogicException $e) {
                $sortOrderParams['field'] = $fieldName;
            }

            $sortOrders[] = $factory->create($sortOrderParams);
        }

        return $sortOrders;
    }

    /**
     * Retrieve base params for a sort order field.
     *
     * @param FieldInterface $field           Sort order field.
     * @param array          $sortOrderParams Sort order params.
     *
     * @return array
     */
    private function getSortOrderParams(FieldInterface $field, array $sortOrderParams)
    {
        $sortOrderParams['field'] = $field->getMappingProperty(FieldInterface::ANALYZER_SORTABLE);

        if ($field->isNested() && !isset($sortOrderParams['nestedPath'])) {
            $sortOrderParams['nestedPath'] = $field->getNestedPath();
        }

        return $sortOrderParams;
    }
}
