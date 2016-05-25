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
 * Product attribute search engine rule.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Magento\Rule\Model\Condition\Product\AbstractProduct
{
    /**
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\AttributeList
     */
    private $attributeList;

    /**
     *
     * @var \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder
     */
    private $queryBuilder;

    /**
     * Constructor.
     *
     * @param \Magento\Rule\Model\Condition\Context                                     $context           Rule context.
     * @param \Magento\Backend\Helper\Data                                              $backendData       Admin helper.
     * @param \Magento\Eav\Model\Config                                                 $config            EAV config.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList     Product search rule attribute list.
     * @param \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder  $queryBuilder      Product search rule query builder.
     * @param \Magento\Catalog\Model\ProductFactory                                     $productFactory    Product factory.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                           $productRepository Product repository.
     * @param \Magento\Catalog\Model\ResourceModel\Product                              $productResource   Product resource model.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection          $attrSetCollection Attribute set collection.
     * @param \Magento\Framework\Locale\FormatInterface                                 $localeFormat      Locale format.
     * @param array                                                                     $data              Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList,
        \Smile\ElasticSuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder $queryBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        array $data = []
    ) {
        $this->attributeList = $attributeList;
        $this->queryBuilder  = $queryBuilder;
        parent::__construct($context, $backendData, $config, $productFactory, $productRepository, $productResource, $attrSetCollection, $localeFormat, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function loadAttributeOptions()
    {
        $productAttributes = $this->attributeList->getAttributeCollection();

        $attributes = [];
        foreach ($productAttributes as $attribute) {
            $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        $this->_addSpecialAttributes($attributes);

        asort($attributes);
        $this->setAttributeOption($attributes);

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
     * @return \Smile\ElasticSuiteCore\Search\Request\QueryInterface
     */
    public function getSearchQuery()
    {
        return $this->queryBuilder->getSearchQuery($this);
    }

    /**
     *
     * {@inheritDoc}
     */
    public function getInputType()
    {
        $inputType = 'string';

        if ($this->getAttribute() === 'attribute_set_id') {
            $inputType = 'select';
        } elseif ($this->getAttribute() === 'price') {
            $inputType = 'numeric';
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();

            if ($this->getAttributeObject()->getAttributeCode() == 'category_ids') {
                $inputType = 'category';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $inputType = 'multiselect';
            } elseif ($frontendInput == 'date') {
                $inputType = 'date';
            } elseif ($frontendInput == 'boolean') {
                $inputType = 'boolean';
            }
        }

        return $inputType;
    }

    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        $valueElementType = 'text';

        if ($this->getAttribute() === 'attribute_set_id') {
            $valueElementType = 'select';
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();

            if ($frontendInput == 'boolean') {
                $valueElementType = 'select';
            } elseif ($frontendInput == 'date') {
                $valueElementType = 'date';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $valueElementType = 'multiselect';
            }
        }

        return $valueElementType;
    }

    /**
     * Default operator input by type map getter
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            $this->_defaultOperatorInputByType = [
                'string' => ['{}', '!{}'],
                'numeric' => ['==', '!=', '>=', '>', '<=', '<'],
                'date' => ['==', '>=', '>', '<=', '<'],
                'select' => ['==', '!='],
                'boolean' => ['==', '!='],
                'multiselect' => ['()', '!()'],
                'grid' => ['()', '!()'],
                'category' => ['()', '!()'],
            ];
            $this->_arrayInputTypes = ['multiselect', 'grid', 'category'];
        }

        return $this->_defaultOperatorInputByType;
    }
}
