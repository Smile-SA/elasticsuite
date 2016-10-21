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

        $products = $this->getSearchableProducts($storeId, $productIds);

        foreach ($products as $productData) {
            $productId = (int) $productData['entity_id'];
            yield $productId => $productData;
        }
    }

    /**
     * Load a bulk of product data.
     *
     * @param int    $storeId    Store id.
     * @param string $productIds Product ids filter.
     *
     * @return array
     */
    private function getSearchableProducts($storeId, $productIds = null)
    {
        return $this->resourceModel->getSearchableProducts($storeId, $productIds);
    }
}
