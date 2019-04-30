<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Model;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Catalog search engine rule.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Rule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory
     */
    protected $conditionsFactory;

    /**
     * @var string
     */
    protected $elementName;

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\Data\ConditionFactory
     */
    private $conditionDataFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\Context                                   $context              Context.
     * @param \Magento\Framework\Registry                                        $registry             Registry.
     * @param \Magento\Framework\Data\FormFactory                                $formFactory          Form factory.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface               $localeDate           Locale date.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory $conditionsFactory    Search engine rule condition factory.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Data\ConditionFactory         $conditionDataFactory Condition Data Factory.
     * @param array                                                              $data                 Additional data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\CombineFactory $conditionsFactory,
        \Smile\ElasticsuiteCatalogRule\Model\Data\ConditionFactory $conditionDataFactory,
        array $data = []
    ) {
        $this->conditionsFactory    = $conditionsFactory;
        $this->conditionDataFactory = $conditionDataFactory;
        parent::__construct($context, $registry, $formFactory, $localeDate, null, null, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getConditionsInstance()
    {
        $condition = $this->conditionsFactory->create();
        $condition->setRule($this);
        $condition->setElementName($this->elementName);

        return $condition;
    }

    /**
     * {@inheritDoc}
     */
    public function getActionsInstance()
    {
        throw new \LogicException('Unsupported method.');
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
     * {@inheritDoc}
     */
    public function getConditions()
    {
        $conditions = parent::getConditions();
        $conditions->setRule($this);
        $conditions->setElementName($this->elementName);

        return $conditions;
    }

    /**
     * Build a search query for the current rule.
     *
     * @return QueryInterface
     */
    public function getSearchQuery()
    {
        $query      = null;
        $conditions = $this->getConditions();

        if ($conditions) {
            $query = $conditions->getSearchQuery();
        }

        return $query;
    }

    /**
     * Convert recursive array into condition data model
     *
     * @param array $input Conditions arrays.
     *
     * @return \Smile\ElasticsuiteCatalogRule\Model\Data\Condition
     */
    protected function arrayToConditionDataModel(array $input)
    {
        /** @var \Smile\ElasticsuiteCatalogRule\Model\Data\Condition $conditionDataModel */
        $conditionDataModel = $this->conditionDataFactory->create();
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'type':
                    $conditionDataModel->setConditionType($value);
                    break;
                case 'attribute':
                    $conditionDataModel->setAttributeName($value);
                    break;
                case 'operator':
                    $conditionDataModel->setOperator($value);
                    break;
                case 'value':
                    $conditionDataModel->setValue($value);
                    break;
                case 'aggregator':
                    $conditionDataModel->setAggregatorType($value);
                    break;
                case 'conditions':
                    $conditions = [];
                    foreach ($value as $condition) {
                        $conditions[] = $this->arrayToConditionDataModel($condition);
                    }
                    $conditionDataModel->setConditions($conditions);
                    break;
                default:
            }
        }

        return $conditionDataModel;
    }

    /**
     * Convert recursive array into condition data model
     *
     * @param \Smile\ElasticsuiteCatalogRule\Model\Data\Condition $condition The condition
     * @param string                                              $key       The key
     *
     * @return array
     */
    protected function dataModelToArray(\Smile\ElasticsuiteCatalogRule\Model\Data\Condition $condition, $key = 'conditions')
    {
        $output              = [];
        $output['type']      = $condition->getConditionType();
        $output['value']     = $condition->getValue();
        $output['attribute'] = $condition->getAttributeName();
        $output['operator']  = $condition->getOperator();

        if ($condition->getAggregatorType()) {
            $output['aggregator'] = $condition->getAggregatorType();
        }
        if ($condition->getConditions()) {
            $conditions = [];
            foreach ($condition->getConditions() as $subCondition) {
                $conditions[] = $this->dataModelToArray($subCondition, $key);
            }
            $output[$key] = $conditions;
        }

        return $output;
    }
}
