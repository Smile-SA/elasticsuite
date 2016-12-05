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
namespace Smile\ElasticsuiteVirtualCategory\Model\Layer\Filter\DataProvider;

use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory as CategoryModelFactory;
use Magento\Catalog\Model\Layer;
use Magento\Framework\Registry;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Custom Data Provider for Virtual Category Filter
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Category extends \Magento\Catalog\Model\Layer\Filter\DataProvider\Category
{
    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * @param Registry             $coreRegistry        Core Registry
     * @param CategoryModelFactory $categoryFactory     Category Factory
     * @param Layer                $layer               Layer
     * @param VirtualCategoryRoot  $virtualCategoryRoot Virtual Category Root
     */
    public function __construct(
        Registry $coreRegistry,
        CategoryModelFactory $categoryFactory,
        Layer $layer,
        VirtualCategoryRoot $virtualCategoryRoot
    ) {
        $this->virtualCategoryRoot = $virtualCategoryRoot;

        parent::__construct($coreRegistry, $categoryFactory, $layer);
    }

    /**
     * Retrieve the currently applied root category.
     * This is true when browsing a subtree of a virtual category having a "Virtual Root Category".
     *
     * @return \Magento\Catalog\Api\Data\CategoryInterface|null
     */
    public function getAppliedRootCategory()
    {
        return $this->virtualCategoryRoot->getAppliedRootCategory();
    }
}
