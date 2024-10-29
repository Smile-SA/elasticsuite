<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Catalog\Category;

use Smile\ElasticsuiteCatalog\Plugin\Indexer\AbstractIndexerPlugin;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full as FullIndexer;

/**
 * Save the category product sorting at save time.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SaveProductsPositions extends AbstractIndexerPlugin
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position
     */
    private $saveHandler;

    /**
     * ReindexProductsAfterSave constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry                                       $indexerRegistry The indexer registry.
     * @param FullIndexer                                                                      $fullIndexer     The Full Indexer
     * @param \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position $saveHandler     Product position
     *                                                                                                          save handler.
     * @param \Magento\Framework\Json\Helper\Data                                              $jsonHelper      JSON Helper.
     * @param \Magento\Framework\Message\ManagerInterface                                      $messageManager  Message Manager.
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        FullIndexer $fullIndexer,
        \Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\Category\Product\Position $saveHandler,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($indexerRegistry, $fullIndexer);

        $this->jsonHelper  = $jsonHelper;
        $this->saveHandler = $saveHandler;
        $this->messageManager = $messageManager;
    }

    /**
     * Resource model save function plugin.
     * Append a commit callback to save the product positions.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category $categoryResource Category original resource model.
     * @param \Closure                                      $proceed          Original save method.
     * @param \Magento\Framework\Model\AbstractModel        $category         Saved category.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category
     */
    public function aroundSave(
        \Magento\Catalog\Model\ResourceModel\Category $categoryResource,
        \Closure $proceed,
        \Magento\Framework\Model\AbstractModel $category
    ) {
        if ($category->getId() && $category->getSortedProducts()) {
            $this->unserializeProductPositions($category);

            if ($category->getIsVirtualCategory()) {
                $category->setPostedProducts([]);
            }

            $categoryResource->addCommitCallback(
                function () use ($category) {
                    $affectedProductIds = $this->getAffectedProductIds($category);
                    $category->setAffectedProductIds($affectedProductIds);
                    $this->saveHandler->saveProductPositions($category);
                }
            );
        }

        return $proceed($category);
    }

    /**
     * List of product that have been moved during the save.
     *
     * @param \Magento\Catalog\Model\Category $category Category
     *
     * @return array
     */
    private function getAffectedProductIds($category)
    {
        $oldPositionProductIds     = array_keys($this->saveHandler->getProductPositionsByCategory($category));
        $defaultPositionProductIds = [];
        $newPositionProductIds     = array_keys($category->getSortedProducts());

        $oldBlacklistedProductIds     = array_values($this->saveHandler->getProductBlacklistByCategory($category));
        $defaultBlacklistedProductIds = [];
        $newBlacklistedProductIds     = array_values($category->getBlacklistedProducts() ?? []);

        if (true === (bool) $category->getUseStorePositions()) {
            $defaultPositionProductIds = array_keys(
                $this->saveHandler->getProductPositions(
                    $category->getId(),
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                )
            );

            $defaultBlacklistedProductIds = array_values(
                $this->saveHandler->getProductBlacklist(
                    $category->getId(),
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                )
            );
        }

        $affectedProductIds = array_merge(
            $oldPositionProductIds,
            $defaultPositionProductIds,
            $newPositionProductIds,
            $oldBlacklistedProductIds,
            $defaultBlacklistedProductIds,
            $newBlacklistedProductIds
        );

        if ($category->getAffectedProductIds()) {
            $affectedProductIds = array_merge($affectedProductIds, $category->getAffectedProductIds());
        }

        return array_unique($affectedProductIds);
    }

    /**
     * Unserialize the sorted_products field of category if it is a string value.
     *
     * @param \Magento\Catalog\Model\Category $category Category
     *
     * @return $this
     */
    private function unserializeProductPositions(\Magento\Catalog\Model\Category $category)
    {
        // Get product positions from the category.
        $productPositions = $category->getSortedProducts() ? $category->getSortedProducts() : [];

        if (is_string($productPositions)) {
            try {
                $productPositions = $this->jsonHelper->jsonDecode($productPositions);
            } catch (\Exception $e) {
                $this->messageManager->addWarningMessage(
                    __('Something went wrong while saving your product positions, they have been switched back to their last known state.')
                );

                // Fallback to the last known valid product positions.
                $productPositions = $this->saveHandler->getProductPositionsByCategory($category);
            }
        }

        $category->setSortedProducts($productPositions);

        return $this;
    }
}
