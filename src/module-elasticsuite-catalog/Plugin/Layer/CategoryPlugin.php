<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Layer;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Catalog Category Layer Plugin.
 * Used to instantiate Search Context when setting current category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryPlugin
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Context
     */
    private $searchContext;

    /**
     * CategoryPlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Context $searchContext Search Context
     */
    public function __construct(\Smile\ElasticsuiteCore\Search\Context $searchContext)
    {
        $this->searchContext = $searchContext;
    }

    /**
     * Set the current layer category into the Search context after being assigned to the layer.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\Catalog\Model\Layer\Category       $layer    The layer
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category Current Category
     *
     * @return \Magento\Catalog\Model\Layer\Category
     */
    public function afterGetCurrentCategory(
        \Magento\Catalog\Model\Layer\Category $layer,
        \Magento\Catalog\Api\Data\CategoryInterface $category
    ) {
        if ($this->searchContext->getCurrentCategory() === null) {
            $this->searchContext->setCurrentCategory($category);
        }

        return $category;
    }
}
