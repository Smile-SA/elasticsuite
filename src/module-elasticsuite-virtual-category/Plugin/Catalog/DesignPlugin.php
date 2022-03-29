<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog;

use Magento\Catalog\Model\Design;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root as VirtualCategoryRoot;

/**
 * Plugin for Catalog Design, to ensure discarding custom design for categories being viewed under the subtree of a virtual category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DesignPlugin
{
    /**
     * @var VirtualCategoryRoot
     */
    private $virtualCategoryRoot;

    /**
     * DesignPlugin constructor.
     *
     * @param VirtualCategoryRoot $virtualCategoryRoot Virtual Category Root Model
     */
    public function __construct(VirtualCategoryRoot $virtualCategoryRoot)
    {
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * Discard custom rendering for category if being viewed under a virtual root category.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Design                                                         $design  Catalog Design Model
     * @param \Closure                                                       $proceed The legacy getDesignSettings method
     * @param \Magento\Catalog\Model\Category|\Magento\Catalog\Model\Product $object  Catalog Object
     *
     * @return mixed
     */
    public function aroundGetDesignSettings(Design $design, \Closure $proceed, $object)
    {
        if (!($object instanceof \Magento\Catalog\Model\Product)) {
            if ($this->virtualCategoryRoot->getAppliedRootCategory()) {
                return new \Magento\Framework\DataObject();
            }
        }

        return $proceed($object);
    }
}
