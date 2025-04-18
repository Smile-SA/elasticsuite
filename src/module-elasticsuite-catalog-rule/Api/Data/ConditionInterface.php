<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Api\Data;

/**
 * Rule Conditions Data Interface (mostly used for API)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ConditionInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const AGGREGATOR_TYPE_ALL = 'all';
    const AGGREGATOR_TYPE_ANY = 'any';

    /**
     * Get condition type
     *
     * @return string
     */
    public function getConditionType();

    /**
     * @param string $conditionType Condition Type
     *
     * @return $this
     */
    public function setConditionType($conditionType);

    /**
     * Return list of conditions
     *
     * @return \Smile\ElasticsuiteCatalogRule\Api\Data\ConditionInterface[]|null
     */
    public function getConditions();

    /**
     * Set conditions
     *
     * @param \Smile\ElasticsuiteCatalogRule\Api\Data\ConditionInterface[]|null $conditions Conditions
     *
     * @return $this
     */
    public function setConditions(?array $conditions = null);

    /**
     * Return the aggregator type
     *
     * @return string|null
     */
    public function getAggregatorType();

    /**
     * Set the aggregator type
     *
     * @param string $aggregatorType Aggregator Type
     *
     * @return $this
     */
    public function setAggregatorType($aggregatorType);

    /**
     * Return the operator of the condition
     *
     * @return string
     */
    public function getOperator();

    /**
     * Set the operator of the condition
     *
     * @param string $operator Operator
     *
     * @return $this
     */
    public function setOperator($operator);

    /**
     * Return the attribute name of the condition
     *
     * @return string|null
     */
    public function getAttributeName();

    /**
     * Set the attribute name of the condition
     *
     * @param string $attributeName Attribute Name
     *
     * @return $this
     */
    public function setAttributeName($attributeName);

    /**
     * Return the value of the condition
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Set the value of the condition
     *
     * @param mixed $value The value
     *
     * @return $this
     */
    public function setValue($value);
}
