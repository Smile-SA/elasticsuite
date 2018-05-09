<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;

/**
 * ElasticSuite product attributes helper.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class ProductAttribute extends AbstractAttribute
{
    /**
     * Constructor.
     *
     * @param Context                    $context           Helper context.
     * @param AttributeFactory           $attributeFactory  Factory used to create attributes.
     * @param AttributeCollectionFactory $collectionFactory Attribute collection factory.
     */
    public function __construct(Context $context, AttributeFactory $attributeFactory, AttributeCollectionFactory $collectionFactory)
    {
        parent::__construct($context, $attributeFactory, $collectionFactory);
    }
}
