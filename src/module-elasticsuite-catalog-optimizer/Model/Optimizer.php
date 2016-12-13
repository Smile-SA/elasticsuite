<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model;

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogRule\Model\RuleFactory;
use Magento\Framework\Stdlib\DateTime\Filter\Date;

/**
 * Optimizer Model
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Optimizer extends \Magento\Framework\Model\AbstractModel implements OptimizerInterface
{
    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    private $dateFilter;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\Context                        $context            Context.
     * @param \Magento\Framework\Registry                             $registry           Registry.
     * @param RuleFactory                                             $ruleFactory        Rule factory.
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date          $dateFilter         Date Filter.
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource           Resource.
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection Resource collection.
     * @param array                                                   $data               Data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        RuleFactory $ruleFactory,
        Date $dateFilter,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->ruleFactory = $ruleFactory;
        $this->dateFilter  = $dateFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        $this->parseDateFields();

        parent::beforeSave();
    }

    /**
     * Get Optimizer ID.
     *
     * @return int|null
     */
    public function getOptimizerId()
    {
        return $this->getId();
    }

    /**
     * Retrieve Optimizer name.
     *
     * @return string
     */
    public function getName()
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Get Optimizer status.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * Get Optimizer model.
     *
     * @return string
     */
    public function getModel()
    {
        return (string) $this->getData(self::MODEL);
    }

    /**
     * Get Optimizer config.
     *
     * @param Key $key Key in array.
     *
     * @return array|string
     */
    public function getConfig($key = null)
    {
        if (is_string($this->getData(self::CONFIG))) {
            $this->setData(self::CONFIG, unserialize($this->getData(self::CONFIG)));
        }

        $result = $this->getData(self::CONFIG);

        if ($key !== null) {
            $result = isset($result[$key]) ? $result[$key] : null;
        }

        return $result;
    }

    /**
     * Get Optimizer store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * Get Optimizer from date.
     *
     * @return string
     */
    public function getFromDate()
    {
        return (string) $this->getData(self::FROM_DATE);
    }

    /**
     * Get Optimizer to date.
     *
     * @return string
     */
    public function getToDate()
    {
        return (string) $this->getData(self::TO_DATE);
    }

    /**
     * Get Optimizer search container.
     *
     * @return string
     */
    public function getSearchContainer()
    {
        return $this->getData(self::SEARCH_CONTAINER);
    }

    /**
     * Get Optimizer rule condition.
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Api\Data\VirtualRuleInterface
     */
    public function getRuleCondition()
    {
        if (!is_object($this->getData(self::RULE_CONDITION))) {
            $ruleData = $this->getData(self::RULE_CONDITION);
            $rule     = $this->ruleFactory->create();

            if (is_string($ruleData)) {
                $ruleData = unserialize($ruleData);
            }

            if (is_array($ruleData)) {
                $rule->getConditions()->loadArray($ruleData);
            }
            $this->setData(self::RULE_CONDITION, $rule);
        }

        return $this->getData(self::RULE_CONDITION);
    }

    /**
     * Set name.
     *
     * @param string $name the value to save.
     *
     * @return Optimizer
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, (string) $name);
    }

    /**
     * Set Optimizer status.
     *
     * @param bool $status The Optimizer status.
     *
     * @return Optimizer
     */
    public function setIsActive($status)
    {
        return $this->setData(self::IS_ACTIVE, (bool) $status);
    }

    /**
     * Set Optimizer model.
     *
     * @param string $model The Optimizer model.
     *
     * @return Optimizer
     */
    public function setModel($model)
    {
        return $this->setData(self::MODEL, (string) $model);
    }

    /**
     * Set Optimizer config.
     *
     * @param string|array $config The Optimizer config.
     *
     * @return Optimizer
     */
    public function setConfig($config)
    {
        return $this->setData(self::CONFIG, $config);
    }

    /**
     * Set Optimizer store id.
     *
     * @param int $storeId The Optimizer store id.
     *
     * @return Optimizer
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, (int) $storeId);
    }

    /**
     * Set Optimizer from date.
     *
     * @param string|null $fromDate The Optimizer from date.
     *
     * @return Optimizer
     */
    public function setFromDate($fromDate)
    {
        return $this->setData(self::FROM_DATE, (string) $fromDate);
    }

    /**
     * Set Optimizer to date.
     *
     * @param string|null $toDate The Optimizer to date.
     *
     * @return Optimizer
     */
    public function setToDate($toDate)
    {
        return $this->setData(self::TO_DATE, (string) $toDate);
    }

    /**
     * Set Optimizer search container.
     *
     * @param string $searchContainer The Optimizer search container.
     *
     * @return Optimizer
     */
    public function setSearchContainer($searchContainer)
    {
        return $this->setData(self::SEARCH_CONTAINER, $searchContainer);
    }

    /**
     * Set Optimizer rule condition.
     *
     * @param string $ruleCondition The Optimizer rule condition.
     *
     * @return Optimizer
     */
    public function setRuleCondition($ruleCondition)
    {
        return $this->setData(self::RULE_CONDITION, $ruleCondition);
    }

    /**
     * Validate optimizer data
     *
     * @param \Magento\Framework\DataObject $dataObject The Optimizer
     *
     * @return bool|string[] - return true if validation passed successfully. Array with errors description otherwise
     */
    public function validateData(\Magento\Framework\DataObject $dataObject)
    {
        $result = [];
        $fromDate = $toDate = null;

        if ($dataObject->hasFromDate() && $dataObject->hasToDate()) {
            $fromDate = $dataObject->getFromDate();
            $toDate = $dataObject->getToDate();
        }

        if ($fromDate && $toDate) {
            $fromDate = new \DateTime($fromDate);
            $toDate = new \DateTime($toDate);

            if ($fromDate > $toDate) {
                $result[] = __('End Date must follow Start Date.');
            }
        }

        return !empty($result) ? $result : true;
    }

    /**
     * Internal Constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init('Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer');
    }

    /**
     * Parse date related fields
     *
     * @throws \Exception
     */
    private function parseDateFields()
    {
        if ('' === $this->getFromDate()) {
            $this->unsFromDate();
        }

        if ('' === $this->getToDate()) {
            $this->unsToDate();
        }

        foreach ([self::FROM_DATE, self::TO_DATE] as $dateField) {
            if ($this->hasData($dateField) && is_string($this->getData($dateField))) {
                $this->setData($dateField, $this->dateFilter->filter($this->getData($dateField)));
            }
        }
    }
}
