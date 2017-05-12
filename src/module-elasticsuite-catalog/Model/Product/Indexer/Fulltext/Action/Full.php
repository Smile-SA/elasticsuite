<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Action;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full as ResourceModel;

/**
 * Elasticsearch product full indexer.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Full
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Action\Full
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
     * Get data for a list of product in a store id.
     * If the product list ids is null, all products data will be loaded.
     *
     * @param integer    $storeId    Store id.
     * @param array|null $productIds List of product ids.
     *
     * @return array
     */
    public function rebuildStoreIndex($storeId, $productIds = null)
    {
        $productId = 0;

        do {
            $products = $this->getSearchableProducts($storeId, $productIds, $productId);

            foreach ($products as $productData) {
                $productId = (int) $productData['entity_id'];
                yield $productId => $productData;
            }
        } while (!empty($products));
    }

    /**
     * Load a bulk of product data.
     *
     * @param int     $storeId    Store id.
     * @param string  $productIds Product ids filter.
     * @param integer $fromId     Load product with id greater than.
     * @param integer $limit      Number of product to get loaded.
     *
     * @return array
     */
    private function getSearchableProducts($storeId, $productIds = null, $fromId = 0, $limit = 10000)
    {
        return $this->resourceModel->getSearchableProducts($storeId, $productIds, $fromId, $limit);
    }
}
