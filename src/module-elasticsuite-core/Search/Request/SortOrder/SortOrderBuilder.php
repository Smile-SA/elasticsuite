<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\SortOrder;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Search\Request\Query\Filter\QueryBuilder;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;

/**
 * Allow to build a sort order from arrays.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @param ContainerConfigurationInterface $containerConfig Request configuration.
     * @param array                           $orders          Sort orders definitions.
     *
     * @return SortOrderInterface[]
     */
    public function buildSordOrders(ContainerConfigurationInterface $containerConfig, array $orders)
    {
        $sortOrders = [];
        $mapping    = $containerConfig->getMapping();

        $orders = $this->addDefaultSortOrders($orders, $mapping);

        foreach ($orders as $fieldName => $sortOrderParams) {
            $factory = $this->standardOrderFactory;

            try {
                $sortField       = $mapping->getField($fieldName);
                $sortOrderParams = $this->getSortOrderParams($sortField, $sortOrderParams);

                if (isset($sortOrderParams['nestedPath'])) {
                    $factory = $this->nestedOrderFactory;
                }

                if (isset($sortOrderParams['nestedFilter'])) {
                    $nestedFilter = $this->queryBuilder->create(
                        $containerConfig,
                        $sortOrderParams['nestedFilter'],
                        $sortOrderParams['nestedPath']
                    );
                    $sortOrderParams['nestedFilter'] = $nestedFilter;
                }
            } catch (\LogicException $e) {
                $sortOrderParams['field'] = $fieldName;
            }

            $sortOrders[] = $factory->create($sortOrderParams);
        }

        return $sortOrders;
    }

    /**
     * Append default sort to all queries to get fully predictable search results.
     *
     * Order by _score first and then by the id field.
     *
     * @param array            $orders  Original orders.
     * @param MappingInterface $mapping Mapping.
     *
     * @return array
     */
    private function addDefaultSortOrders($orders, MappingInterface $mapping)
    {
        $defaultOrders = [
            SortOrderInterface::DEFAULT_SORT_FIELD => SortOrderInterface::SORT_DESC,
            $mapping->getIdField()->getName()      => SortOrderInterface::SORT_DESC,
        ];

        if (count($orders) > 0) {
            $firstOrder = current($orders);
            if ($firstOrder['direction'] == SortOrderInterface::SORT_DESC) {
                $defaultOrders[SortOrderInterface::DEFAULT_SORT_FIELD] = SortOrderInterface::SORT_ASC;
                $defaultOrders[$mapping->getIdField()->getName()]      = SortOrderInterface::SORT_ASC;
            }
        }

        foreach ($defaultOrders as $currentOrder => $direction) {
            if (!in_array($currentOrder, array_keys($orders))) {
                $orders[$currentOrder] = ['direction' => $direction];
            }
        }

        return $orders;
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
        $sortOrderParams['field']   = $field->getMappingProperty(FieldInterface::ANALYZER_SORTABLE);
        $sortOrderParams['missing'] = $field->getSortMissing($sortOrderParams['direction']);

        if ($field->isNested() && !isset($sortOrderParams['nestedPath'])) {
            $sortOrderParams['nestedPath'] = $field->getNestedPath();
        }

        return $sortOrderParams;
    }
}
