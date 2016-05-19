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
namespace Smile\ElasticSuiteCatalogRule\Model;

/**
 * Catalog search engine rule.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Rule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory
     */
    protected $conditionsFactory;

    /**
     * @var string
     */
    protected $elementName;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\Model\Context                                   $context            Context.
     * @param \Magento\Framework\Registry                                        $registry           Registry.
     * @param \Magento\Framework\Data\FormFactory                                $formFactory        Form factory.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface               $localeDate         Locale date.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory $conditionsFactory  Search engine rule condition factory.
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource            $resource           Base resource model.
     * @param \Magento\Framework\Data\Collection\AbstractDb                      $resourceCollection Base resource collection.
     * @param array                                                              $data               Additional data.
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\CombineFactory $conditionsFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->conditionsFactory = $conditionsFactory;
        parent::__construct($context, $registry, $formFactory, $localeDate, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getConditionsInstance()
    {
        $condition = $this->conditionsFactory->create();
        $condition->setElementName($this->elementName);

        return $condition;
    }

    /**
     * {@inheritDoc}
     */
    public function getActionsInstance()
    {
        throw new \LogicalException('Unsupported method.');
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
        $conditions->setElementName($this->elementName);

        return $conditions;
    }
}
