<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Optimizer Model
 *
 * @SuppressWarnings(CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Optimizer extends \Magento\Framework\Model\AbstractModel implements OptimizerInterface, IdentityInterface
{
    /**
     * @var string
     */
    const CACHE_TAG = 'smile_optimizer';

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\RuleFactory
     */
    private $ruleFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    private $dateFilter;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Limitation\IdentitiesFactory
     */
    private $limitationIdentitiesFactory;

    /**
    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\Model\Context                             $context                     Context.
     * @param \Magento\Framework\Registry                                  $registry                    Registry.
     * @param \Smile\ElasticsuiteCatalogRule\Model\RuleFactory             $ruleFactory                 Rule factory.
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date               $dateFilter                  Date Filter.
     * @param \Magento\Framework\Serialize\SerializerInterface             $serializer                  Serializer.
     * @param Optimizer\Limitation\IdentitiesFactory                       $limitationIdentitiesFactory Limitation Identities.
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource                    Resource.
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection          Resource collection.
     * @param array                                                        $data                        Data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Smile\ElasticsuiteCatalogRule\Model\RuleFactory  $ruleFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        Optimizer\Limitation\IdentitiesFactory $limitationIdentitiesFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->ruleFactory                 = $ruleFactory;
        $this->dateFilter                  = $dateFilter;
        $this->serializer                  = $serializer;
        $this->limitationIdentitiesFactory = $limitationIdentitiesFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave()
    {
        $this->parseDateFields();

        return parent::beforeSave();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->getData(self::OPTIMIZER_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * {@inheritDoc}
     */
    public function getModel()
    {
        return (string) $this->getData(self::MODEL);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig($key = null)
    {
        if (is_string($this->getData(self::CONFIG))) {
            $this->setData(self::CONFIG, $this->serializer->unserialize($this->getData(self::CONFIG)));
        }

        $result = $this->getData(self::CONFIG);

        if ($key !== null) {
            $result = isset($result[$key]) ? $result[$key] : null;
        }

        return $result;
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getStoreId()
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * {@inheritDoc}
     */
    public function getFromDate()
    {
        return (string) $this->getData(self::FROM_DATE);
    }

    /**
     * {@inheritDoc}
     */
    public function getToDate()
    {
        return (string) $this->getData(self::TO_DATE);
    }

    /**
     * {@inheritDoc}
     */
    public function getSearchContainers()
    {
        return $this->getData('search_containers');
    }

    /**
     * {@inheritDoc}
     */
    public function getRuleCondition()
    {
        if (!is_object($this->getData(self::RULE_CONDITION))) {
            $ruleData = $this->getData(self::RULE_CONDITION);
            $rule     = $this->ruleFactory->create();

            if (is_string($ruleData)) {
                $ruleData = $this->serializer->unserialize($ruleData);
            }

            if (is_array($ruleData)) {
                $rule->getConditions()->loadArray($ruleData);
            }
            $this->setData(self::RULE_CONDITION, $rule);
        }

        return $this->getData(self::RULE_CONDITION);
    }

    /**
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * {@inheritDoc}
     */
    public function setId($id)
    {
        return $this->setData(self::OPTIMIZER_ID, $id);
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, (string) $name);
    }

    /**
     * {@inheritDoc}
     */
    public function setIsActive($status)
    {
        return $this->setData(self::IS_ACTIVE, (bool) $status);
    }

    /**
     * {@inheritDoc}
     */
    public function setModel($model)
    {
        return $this->setData(self::MODEL, (string) $model);
    }

    /**
     * {@inheritDoc}
     */
    public function setConfig($config)
    {
        return $this->setData(self::CONFIG, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, (int) $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function setFromDate($fromDate)
    {
        return $this->setData(self::FROM_DATE, (string) $fromDate);
    }

    /**
     * {@inheritDoc}
     */
    public function setToDate($toDate)
    {
        return $this->setData(self::TO_DATE, (string) $toDate);
    }

    /**
     * {@inheritDoc}
     */
    public function setSearchContainer($searchContainer)
    {
        return $this->setData(self::SEARCH_CONTAINER, $searchContainer);
    }

    /**
     * {@inheritDoc}
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
            $fromDate = $this->dateFilter->filter($fromDate);
            $toDate = $this->dateFilter->filter($toDate);

            if ($fromDate > $toDate) {
                $result[] = __('End Date must follow Start Date.');
            }
        }

        return !empty($result) ? $result : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentities()
    {
        $limitationIdentities = $this->limitationIdentitiesFactory->create(['optimizer' => $this]);
        $identities           = array_merge($this->getCacheTags(), $limitationIdentities->get());

        return $identities;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        $this->_init('Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer');
    }

    /**
     * Parse date related fields
     *
     * @throws \Exception
     *
     * @return void
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
