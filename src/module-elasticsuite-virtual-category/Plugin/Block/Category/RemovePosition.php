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
namespace Smile\ElasticsuiteVirtualCategory\Plugin\Block\Category;

use Magento\Catalog\Block\Adminhtml\Category\Tab\Product as ProductGrid;

/**
 * Remove position from category product grid if the visual merchandiser module is enabled.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class RemovePosition
{
    /**
     * Remove position column from
     *
     * @param ProductGrid $subject  Plugin subject.
     * @param ProductGrid $result   Result.
     * @param string      $columnId Column Id.
     * @return ProductGrid
     */
    public function afterAddColumn(ProductGrid $subject, $result, $columnId)
    {
        if (in_array($columnId, ['position', 'draggable-position'])) {
            $subject->removeColumn($columnId);
        }

        return $result;
    }
}
