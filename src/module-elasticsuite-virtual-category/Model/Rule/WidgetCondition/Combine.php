<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\WidgetCondition;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Combine product search rule conditions for pagebuilder widget.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class Combine extends \Smile\ElasticsuiteVirtualCategory\Model\Rule\Condition\Combine
{
    /**
     * @var string
     */
    protected $elementName = 'parameters';

    /**
     * @var string
     */
    protected $type = 'Smile\ElasticsuiteVirtualCategory\Model\Rule\WidgetCondition\Combine';

    /**
     * Collect validated attributes
     *
     * @param Collection $collection Product collection.
     * @return self
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collectValidatedAttributes(Collection $collection)
    {
        // Create this empty function in order to avoid undefined error
        // @see magento/module-catalog-widget/Block/Product/ProductsList.php:351.
        return $this;
    }
}
