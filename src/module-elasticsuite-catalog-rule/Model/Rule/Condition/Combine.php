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
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory
     */
    protected $productFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context                              $context          Rule context.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory Product condition factory.
     * @param array                                                              $data             Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\ProductFactory $conditionFactory,
        array $data = []
    ) {
        $this->productFactory = $conditionFactory;
        parent::__construct($context, $data);
        $this->setType('Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Combine');
    }

    /**
     * {@inheritDoc}
     */
    public function getNewChildSelectOptions()
    {
        $productAttributes = $this->productFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];

        foreach ($productAttributes as $code => $label) {
            $attributes[] = [
                'value' => 'Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product|' . $code,
                'label' => $label,
            ];
        }

        $conditions = parent::getNewChildSelectOptions();

        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => 'Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Combine',
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
