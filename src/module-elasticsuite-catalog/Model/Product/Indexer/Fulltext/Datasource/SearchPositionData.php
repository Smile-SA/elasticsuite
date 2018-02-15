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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position as ResourceModel;

/**
 * Datasource used to append manual search positions to product data.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SearchPositionData implements DatasourceInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Resource model.
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * {@inheritDoc}
     */
    public function addData($storeId, array $indexData)
    {
        $productIds = array_keys($indexData);
        $searchPositions = $this->resourceModel->getByProductIds($productIds, $storeId);

        foreach ($searchPositions as $currentPosition) {
            $data = [
                'query_id'       => (int) $currentPosition['query_id'],
                'position'       => (int) $currentPosition['position'],
                'is_blacklisted' => (bool) $currentPosition['is_blacklisted'],
            ];

            $indexData[(int) $currentPosition['product_id']]['search_query'][] = array_filter($data);
        }

        return $indexData;
    }
}
