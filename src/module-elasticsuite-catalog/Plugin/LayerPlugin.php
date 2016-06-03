<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCatalog\Plugin;

use Magento\CatalogInventory\Model\Plugin\Layer;

/**
 * Replace is in stock native filter on layer.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class LayerPlugin extends \Magento\CatalogInventory\Model\Plugin\Layer
{
    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

    /**
     * Constructor.
     *
     * @param \Magento\CatalogInventory\Helper\Stock             $stockHelper  Stock helper.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig  Configuration.
     * @param \Magento\Search\Model\QueryFactory                 $queryFactory Search query factory.
     */
    public function __construct(
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Search\Model\QueryFactory $queryFactory
    ) {
        parent::__construct($stockHelper, $scopeConfig);
        $this->queryFactory = $queryFactory;
    }


    /**
     * {@inheritDoc}
     */
    public function beforePrepareProductCollection(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        if ($this->_isEnabledShowOutOfStock() === false) {
            $collection->addIsInStockFilter();
        }

        $this->setSortParams($layer, $collection);
    }

    /**
     * Apply sort params to the collection.
     *
     * @param \Magento\Catalog\Model\Layer                                       $layer      Catalog / search layer.
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection Product collection.
     *
     * @return $this
     */
    private function setSortParams(
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        $searchQuery = $this->queryFactory->get();

        if (!$searchQuery->getQueryText() && $layer->getCurrentCategory()) {
            $categoryId = $layer->getCurrentCategory()->getId();
            $collection->addSortFilterParameters('position', 'category.position', 'category', ['category.category_id' => $categoryId]);
        }

        return $this;
    }
}
