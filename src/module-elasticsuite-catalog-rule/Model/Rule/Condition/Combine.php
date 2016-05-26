<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalogRule\Model\Rule\Condition;

use Smile\ElasticSuiteCore\Search\Request\QueryInterface;

/**
 * Product attributes combination search engine rule.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Combine extends \Magento\Rule\Model\Condition\Combine
{
    /**
     * @var string
     */
    protected $type = 'Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Combine';

    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory
     */
    protected $productConditionFactory;

    /**
     * @var \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory
     */
    protected $queryFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context                              $context          Rule context.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory Product condition factory.
     * @param \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory          $queryFactory     Search query factory.
     * @param array                                                              $data             Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory,
        \Smile\ElasticSuiteCore\Search\Request\Query\QueryFactory $queryFactory,
        array $data = []
    ) {
        $this->productConditionFactory = $conditionFactory;
        $this->queryFactory   = $queryFactory;

        parent::__construct($context, $data);
        $this->setType($this->type);
    }

    /**
     * {@inheritDoc}
     */
    public function getNewChildSelectOptions()
    {
        $productAttributes = $this->productConditionFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];

        $productConditionType = get_class($this->productConditionFactory->create());

        foreach ($productAttributes as $code => $label) {
            $attributes[] = [
                'value' => $productConditionType . '|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();

        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => $this->getType(),
                    'label' => __('Conditions Combination'),
                ],
                [
                    'label' => __('Product Attribute'),
                    'value' => $attributes,
                ],
            ]
        );

        return $conditions;
    }

    /**
     * {@inheritDoc}
     */
    public function loadArray($arr, $key = 'conditions')
    {
        $aggregator = $this->getAggregatorFromArray($arr);
        $value      = $this->getValueFromArray($arr);

        $this->setAggregator($aggregator)
            ->setValue($value);

        if (!empty($arr[$key]) && is_array($arr[$key])) {
            foreach ($arr[$key] as $conditionArr) {
                try {
                    $condition = $this->_conditionFactory->create($conditionArr['type']);
                    $condition->setElementName($this->elementName);
                    $condition->setRule($this->getRule());
                    $this->addCondition($condition);
                    $condition->loadArray($conditionArr, $key);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                }
            }
        }

        return $this;
    }

    /**
     * Set the target element name (name of the input into the form).
     *
     * @param string $elementName Target element name
     *
     * @return $this
     */
    public function setElementName($elementName)
    {
        $this->elementName = $elementName;

        return $this;
    }

    /**
     * Build a search query for the current rule.
     *
     * @return QueryInterface
     */
    public function getSearchQuery()
    {
        $queryParams = [];

        $aggregator = $this->getAggregator();
        $value      = (bool) $this->getValue();

        $queryClause = $aggregator === 'all' ? 'must' : 'should';

        foreach ($this->getConditions() as $condition) {
            $subQuery = $condition->getSearchQuery();
            if ($subQuery !== null && $subQuery instanceof QueryInterface) {
                $queryParams[$queryClause][] = $subQuery;
            }
        }

        $query = $this->queryFactory->create(QueryInterface::TYPE_BOOL, $queryParams);

        if ($value == false) {
            /* @todo: Check the boolean logic applied is correct regarding to the admin logic */
            $query = $this->queryFactory->create(QueryInterface::TYPE_NOT, ['query' => $query]);
        }

        return $query;
    }

    /**
     * Read the aggregator from an array.
     *
     * @param array $arr Array.
     *
     * @return string|NULL
     */
    private function getAggregatorFromArray($arr)
    {
        return isset($arr['aggregator']) ? $arr['aggregator'] : (isset($arr['attribute']) ? $arr['attribute'] : null);
    }

    /**
     * Read the value from an array.
     *
     * @param array $arr Array.
     *
     * @return mixed|NULL
     */
    private function getValueFromArray($arr)
    {
        return isset($arr['value']) ? $arr['value'] : (isset($arr['operator']) ? $arr['operator'] : null);
    }
}
