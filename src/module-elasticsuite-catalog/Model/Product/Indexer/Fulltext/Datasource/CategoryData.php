<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData as ResourceModel;

/**
 * Datasource used to append categories data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData implements DatasourceInterface
{
    /**
     * @var boolean
     */
    protected $filterZeroPositions;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param ResourceModel $resourceModel       Resource model.
     * @param boolean       $filterZeroPositions Whether to filter out 0 product positions
     */
    public function __construct(ResourceModel $resourceModel, $filterZeroPositions = true)
    {
        $this->resourceModel = $resourceModel;
        $this->filterZeroPositions = $filterZeroPositions;
    }

    /**
     * Add categories data to the index data.
     *
     * {@inheritdoc}
     */
    public function addData($storeId, array $indexData)
    {
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));

        foreach ($categoryData as $categoryDataRow) {
            $productId = (int) $categoryDataRow['product_id'];
            unset($categoryDataRow['product_id']);

            $categoryDataRow = array_merge(
                $categoryDataRow,
                [
                    'category_id' => (int) $categoryDataRow['category_id'],
                    'is_parent'   => (bool) $categoryDataRow['is_parent'],
                    'name'        => (string) $categoryDataRow['name'],
                ]
            );

            if (isset($categoryDataRow['position']) && $categoryDataRow['position'] !== null) {
                $categoryDataRow['position'] = (int) $categoryDataRow['position'];
                if ($this->filterZeroPositions && ($categoryDataRow['position'] === 0)) {
                    unset($categoryDataRow['position']);
                }
            }

            if (isset($categoryDataRow['is_blacklisted'])) {
                $categoryDataRow['is_blacklisted'] = (bool) $categoryDataRow['is_blacklisted'];
            }

            // Filtering out empty, null and false metadata.
            $indexData[$productId]['category'][] = array_filter($categoryDataRow, 'strlen');
        }

        return $indexData;
    }
}
