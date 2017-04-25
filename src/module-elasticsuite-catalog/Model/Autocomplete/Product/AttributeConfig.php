<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Autocomplete\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Helper\Autocomplete as AutocompleteHelper;

/**
 * Autocomplete attribute configuration.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AttributeConfig
{
    /**
     * @var string[]
     */
    private $defaultSelectedAttributes = ['name', 'thumbnail'];

    /**
     * @var string[]
     */
    private $selectedAttributes;

    /**
     * @var AttributeCollection
     */
    private $autocompleteAttributeCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AutocompleteHelper
     */
    private $autocompleteHelper;

    /**
     * Constructor.
     *
     * @param AttributeCollectionFactory $attributeCollectionFactory   Product attribute collection factory.
     * @param StoreManagerInterface      $storeManager                 Store manager.
     * @param AutocompleteHelper         $autocompleteHelper           Autocomplete helper.
     * @param string[]                   $additionalSelectedAttributes Additional product attribute to be added to the \
     *                                                                 product collection.
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        StoreManagerInterface $storeManager,
        AutocompleteHelper $autocompleteHelper,
        $additionalSelectedAttributes = []
    ) {
        $this->storeManager       = $storeManager;
        $this->autocompleteHelper = $autocompleteHelper;

        $this->selectedAttributes = array_merge($this->defaultSelectedAttributes, $additionalSelectedAttributes);

        $this->autocompleteAttributeCollection = $attributeCollectionFactory->create();
        $this->prepareAutocompleteAttributeCollection();
    }

    /**
     * List of attributes selected.
     *
     * @return string[]
     */
    public function getSelectedAttributeCodes()
    {
        return $this->selectedAttributes;
    }

    /**
     * List of attribute selected by default.
     *
     * @return string[]
     */
    public function getDefaultSelectedAttributes()
    {
        return $this->defaultSelectedAttributes;
    }

    /**
     * User defined list of selected attributes.
     *
     * @return string[]
     */
    public function getAdditionalSelectedAttributes()
    {
        return array_diff($this->selectedAttributes, $this->defaultSelectedAttributes);
    }

    /**
     * List of attributes displayed in autocomplete.
     *
     * @return AttributeCollection
     */
    public function getAutocompleteAttributeCollection()
    {
        return $this->autocompleteAttributeCollection;
    }

    /**
     * Get filter field for an attribute.
     *
     * @param ProductAttributeInterface $attribute Product attribute.
     *
     * @return string
     */
    public function getFilterField(ProductAttributeInterface $attribute)
    {
        return $this->autocompleteHelper->getAttributeAutocompleteField($attribute);
    }

    /**
     * Init the list of attribute displayed in autocomplete.
     *
     * @return $this
     */
    private function prepareAutocompleteAttributeCollection()
    {
        $storeId = $this->storeManager->getStore()->getId();

        $this->autocompleteAttributeCollection->addStoreLabel($storeId)
            ->addFieldToFilter('is_displayed_in_autocomplete', true)
            ->load();

        return $this;
    }
}
