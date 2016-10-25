<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition;

/**
 * Product attribute search engine rule.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Product extends \Magento\Rule\Model\Condition\Product\AbstractProduct
{
    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList
     */
    private $attributeList;

    /**
     *
     * @var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private $booleanSource;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param \Magento\Rule\Model\Condition\Context                                     $context           Rule context.
     * @param \Magento\Backend\Helper\Data                                              $backendData       Admin helper.
     * @param \Magento\Eav\Model\Config                                                 $config            EAV config.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList     Product search rule attribute list.
     * @param \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder  $queryBuilder      Product search rule query builder.
     * @param \Magento\Catalog\Model\ProductFactory                                     $productFactory    Product factory.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface                           $productRepository Product repository.
     * @param \Magento\Catalog\Model\ResourceModel\Product                              $productResource   Product resource model.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection          $attrSetCollection Attribute set collection.
     * @param \Magento\Framework\Locale\FormatInterface                                 $localeFormat      Locale format.
     * @param \Magento\Config\Model\Config\Source\Yesno                                 $booleanSource     Data source for boolean select.
     * @param array                                                                     $data              Additional data.
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList $attributeList,
        \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder $queryBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Config\Model\Config\Source\Yesno $booleanSource,
        array $data = []
    ) {
        $this->attributeList = $attributeList;
        $this->queryBuilder  = $queryBuilder;
        $this->booleanSource = $booleanSource;
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
     * @return \Smile\ElasticsuiteCore\Search\Request\QueryInterface
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
        $selectAttributes = ['attribute_set_id', 'stock.is_in_stock', 'has_image', 'price.is_discount'];

        if (in_array($this->getAttribute(), $selectAttributes)) {
            $inputType = 'select';
        } elseif ($this->getAttribute() === 'price') {
            $inputType = 'numeric';
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();

            if ($this->getAttributeObject()->getAttributeCode() === 'category_ids') {
                $inputType = 'category';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $inputType = 'multiselect';
            } elseif ($frontendInput === 'date') {
                $inputType = 'date';
            } elseif ($frontendInput === 'boolean') {
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

        if ($this->getAttribute() == 'attribute_set_id') {
            $valueElementType = 'select';
        } elseif (in_array($this->getAttribute(), ['stock.is_in_stock', 'has_image'])) {
            $valueElementType = 'hidden';
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();

            if ($frontendInput === 'boolean') {
                $valueElementType = 'select';
            } elseif ($frontendInput === 'date') {
                $valueElementType = 'date';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $valueElementType = 'multiselect';
            }
        }

        return $valueElementType;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueName()
    {
        $valueName = parent::getValueName();

        if (in_array($this->getAttribute(), ['stock.is_in_stock', 'has_image', 'price.is_discount'])) {
            $valueName = ' ';
        }

        return $valueName;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperatorName()
    {
        $operatorName = parent::getOperatorName();

        if (in_array($this->getAttribute(), ['stock.is_in_stock', 'has_image', 'price.is_discount'])) {
            $operatorName = ' ';
        }

        return $operatorName;
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

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        if (in_array($this->getAttribute(), ['stock.is_in_stock', 'has_image', 'price.is_discount'])) {
            $this->setData('value', 1);
        }

        return $this->getData('value');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['stock.is_in_stock'] = __('Only in stock products');
        $attributes['price.is_discount'] = __('Only discounted products');
        $attributes['has_image']         = __('Only products with image');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * {@inheritDoc}
     */
    protected function _prepareValueOptions()
    {
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');

        if (in_array($this->getAttribute(), ['stock.is_in_stock', 'has_image', 'price.is_discount'])) {
            $selectOptions = $this->booleanSource->toOptionArray();
            $this->_setSelectOptions($selectOptions, $selectReady, $hashedReady);
        } else {
            parent::_prepareValueOptions();
        }
    }
}
