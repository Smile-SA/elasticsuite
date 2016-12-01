<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;
use Smile\ElasticsuiteCore\Helper\Mapping as MappingHelper;

/**
 * Autocomplete helper for Catalog Autocomplete
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Autocomplete extends \Smile\ElasticsuiteCore\Helper\Autocomplete
{
    /**
     * @var \Magento\CatalogInventory\Api\StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * Constructor.
     *
     * @param Context                     $context            Helper context.
     * @param StoreManagerInterface       $storeManager       Store manager.
     * @param StockConfigurationInterface $stockConfiguration Stock Configuration Interface.
     * @param MappingHelper               $mappingHelper      Mapping helper.
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        MappingHelper $mappingHelper
    ) {
        parent::__construct($context, $storeManager);

        $this->storeManager       = $storeManager;
        $this->stockConfiguration = $stockConfiguration;
        $this->mappingHelper      = $mappingHelper;
    }

    /**
     * Check if Stock configuration allows to display out of stock products
     *
     * @param int $storeId The store Id. Will use current store if null.
     *
     * @return bool
     */
    public function isShowOutOfStock($storeId = null)
    {
        return $this->stockConfiguration->isShowOutOfStock($storeId);
    }

    /**
     * ES field used in attribute autocomplete.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute $attribute Attribute.
     *
     * @return string
     */
    public function getAttributeAutocompleteField(\Magento\Catalog\Api\Data\ProductAttributeInterface $attribute)
    {
        $fieldName = $attribute->getAttributeCode();

        if ($attribute->usesSource()) {
            $fieldName = $this->mappingHelper->getOptionTextFieldName($fieldName);
        }

        return $fieldName;
    }
}
