<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

use Magento\Catalog\Model\ResourceModel\Category\Flat as FlatResource;
use Magento\Framework\DataObject;

/**
 * Plugin to ensure proper loading of virtual rules when using flat categories.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class FlatPlugin
{
    /**
     * Process correct loading of virtual rule when loading a category via the Flat Resource
     * Built around load() method because the afterLoad() Resource method is only triggered
     * when using the EntityManager, and is not triggered when calling load() method on model explicitely.
     *
     * @param FlatResource $categoryResource Flat Category Resource
     * @param \Closure     $proceed          Flat Category legacy load() method
     * @param DataObject   $category         The category being loaded
     * @param string       $value            The value
     * @param null         $field            Field to process loading on
     */
    public function aroundLoad(FlatResource $categoryResource, \Closure $proceed, $category, $value, $field = null)
    {
        $proceed($category, $value, $field);

        if ($category->getVirtualRule() == null || is_string($category->getVirtualRule())) {
            $attribute = $categoryResource->getAttribute('virtual_rule');
            $attribute->getBackend()->afterLoad($category);
        }
    }
}
