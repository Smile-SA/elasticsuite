<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;

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
     * @var Attribute
     */
    private $attributeHelper;

    /**
     * Constructor.
     *
     * @param Context               $context         Helper context.
     * @param StoreManagerInterface $storeManager    Store manager.
     * @param Attribute             $attributeHelper Attribute helper.
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, ProductAttribute $attributeHelper)
    {
        parent::__construct($context, $storeManager);

        $this->attributeHelper = $attributeHelper;
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
        return $this->attributeHelper->getFilterField($attribute);
    }
}
