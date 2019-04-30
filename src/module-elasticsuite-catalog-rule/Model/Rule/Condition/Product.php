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
namespace Smile\ElasticsuiteCatalogRule\Model\Rule\Condition;

use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory as ProductModelFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\Locale\FormatInterface;
use Magento\Rule\Model\Condition\Context;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\AttributeList;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\QueryBuilder;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider;

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
     * @var AttributeList
     */
    private $attributeList;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttributesProvider
     */
    private $specialAttributesProvider;

    /**
     * Constructor.
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param Context                    $context                   Rule context.
     * @param Data                       $backendData               Admin helper.
     * @param Config                     $config                    EAV config.
     * @param AttributeList              $attributeList             Product search rule attribute list.
     * @param QueryBuilder               $queryBuilder              Product search rule query builder.
     * @param ProductModelFactory        $productFactory            Product factory.
     * @param ProductRepositoryInterface $productRepository         Product repository.
     * @param ProductResource            $productResource           Product resource model.
     * @param Collection                 $attrSetCollection         Attribute set collection.
     * @param FormatInterface            $localeFormat              Locale format.
     * @param SpecialAttributesProvider  $specialAttributesProvider Special Attributes Provider
     * @param array                      $data                      Additional data.
     */
    public function __construct(
        Context $context,
        Data $backendData,
        Config $config,
        AttributeList $attributeList,
        QueryBuilder $queryBuilder,
        ProductModelFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductResource $productResource,
        Collection $attrSetCollection,
        FormatInterface $localeFormat,
        SpecialAttributesProvider $specialAttributesProvider,
        array $data = []
    ) {
        $this->attributeList             = $attributeList;
        $this->queryBuilder              = $queryBuilder;
        $this->specialAttributesProvider = $specialAttributesProvider;

        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    public function loadAttributeOptions()
    {
        $attributes        = [];
        $productAttributes = [];
        $this->_addSpecialAttributes($attributes);

        foreach ($this->attributeList->getAttributeCollection() as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $label = sprintf('%s (%s)', $attribute->getFrontendLabel(), $attribute->getAttributeCode());
                $productAttributes[$attribute->getAttributeCode()] = $label;
            }
        }

        asort($productAttributes);
        $attributes += $productAttributes;
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * {@inheritDoc}
     */
    public function getInputType()
    {
        $inputType        = 'string';
        $selectAttributes = ['attribute_set_id'];

        if (in_array($this->getAttribute(), $selectAttributes)) {
            $inputType = 'select';
        } elseif (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $specialAttribute = $this->specialAttributesProvider->getAttribute($this->getAttribute());
            $inputType        = $specialAttribute->getInputType();
        } elseif ($this->getAttribute() === 'price') {
            $inputType = 'numeric';
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();
            $frontendClass = $this->getAttributeObject()->getFrontendClass();

            if ($this->getAttributeObject()->getAttributeCode() === 'category_ids') {
                $inputType = 'category';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $inputType = 'multiselect';
            } elseif (in_array($frontendClass, ['validate-digits', 'validate-number'])) {
                $inputType = 'numeric';
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
        } elseif (is_object($this->getAttributeObject())) {
            $frontendInput = $this->getAttributeObject()->getFrontendInput();

            if ($frontendInput === 'boolean') {
                $valueElementType = 'select';
            } elseif ($frontendInput === 'date') {
                $valueElementType = 'date';
            } elseif (in_array($frontendInput, ['select', 'multiselect'])) {
                $valueElementType = 'multiselect';
            }
        } elseif (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $specialAttribute = $this->specialAttributesProvider->getAttribute($this->getAttribute());
            $valueElementType = $specialAttribute->getValueElementType();
        }

        return $valueElementType;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueName()
    {
        $valueName = parent::getValueName();

        if (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $valueName = $this->specialAttributesProvider->getAttribute($this->getAttribute())->getValueName();
        }

        return $valueName;
    }

    /**
     * {@inheritDoc}
     */
    public function getOperatorName()
    {
        $operatorName = parent::getOperatorName();

        if (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $operatorName = $this->specialAttributesProvider->getAttribute($this->getAttribute())->getOperatorName();
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
                'string'      => ['{}', '!{}'],
                'numeric'     => ['==', '!=', '>=', '>', '<=', '<'],
                'date'        => ['==', '>=', '>', '<=', '<'],
                'select'      => ['==', '!='],
                'boolean'     => ['==', '!='],
                'multiselect' => ['()', '!()'],
                'grid'        => ['()', '!()'],
                'category'    => ['()', '!()'],
            ];
            $this->_arrayInputTypes            = ['multiselect', 'grid', 'category'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        if (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $this->setData('value', $this->specialAttributesProvider->getAttribute($this->getAttribute())->getValue());
        }

        return $this->getData('value');
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);

        foreach ($this->specialAttributesProvider->getList() as $attribute) {
            $attributes[$attribute->getAttributeCode()] = $attribute->getLabel();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * {@inheritDoc}
     */
    protected function _prepareValueOptions()
    {
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');

        if (in_array($this->getAttribute(), array_keys($this->specialAttributesProvider->getList()))) {
            $valueOptions = $this->specialAttributesProvider->getAttribute($this->getAttribute())->getValueOptions();
            $this->_setSelectOptions($valueOptions, $selectReady, $hashedReady);
        } else {
            parent::_prepareValueOptions();
        }
    }
}
