<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category\Indexer\Fulltext\Action;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Indexer\Fulltext\Action\Full as ResourceModel;

/**
 * Elasticsearch categories full indexer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Full
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\Indexer\Fulltext\Action\Full
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Indexer resource model.
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Get data for a list of categories in a store id.
     * If the product list ids is null, all categories data will be loaded.
     *
     * @param integer    $storeId     Store id.
     * @param array|null $categoryIds List of category ids.
     *
     * @return \Traversable
     */
    public function rebuildStoreIndex($storeId, $categoryIds = null)
    {
        $lastCategoryId = 0;

        do {
            $categories = $this->getSearchableCategories($storeId, $categoryIds, $lastCategoryId);

            foreach ($categories as $categoryData) {
                $lastCategoryId = (int) $categoryData['entity_id'];
                yield $lastCategoryId => $categoryData;
            }
        } while (!empty($categories));
    }

    /**
     * Load a bulk of product data.
     *
     * @param int     $storeId     Store id.
     * @param string  $categoryIds Category ids filter.
     * @param integer $fromId      Load product with id greater than.
     * @param integer $limit       Number of product to get loaded.
     *
     * @return array
     */
    private function getSearchableCategories($storeId, $categoryIds = null, $fromId = 0, $limit = 100)
    {
        return $this->resourceModel->getSearchableCategories($storeId, $categoryIds, $fromId, $limit);
    }
}
