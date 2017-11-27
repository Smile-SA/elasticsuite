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

namespace Smile\ElasticsuiteCatalog\Model\Product\Indexer\Fulltext\Datasource;

use Smile\ElasticsuiteCatalog\Api\ProductDataExtensionInterfaceFactory;
use Smile\ElasticsuiteCore\Api\Index\DatasourceInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Indexer\Fulltext\Datasource\CategoryData as ResourceModel;

/**
 * Datasource used to append categories data to product during indexing.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class CategoryData extends AbstractExtensible implements DatasourceInterface
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * CategoryData constructor.
     *
     * @param ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory DataExtensionFactory
     * @param ResourceModel                        $resourceModel                 ResourceModel
     */
    public function __construct(
        ProductDataExtensionInterfaceFactory $dataExtensionInterfaceFactory,
        ResourceModel $resourceModel
    ) {
        parent::__construct($dataExtensionInterfaceFactory);
        $this->resourceModel = $resourceModel;
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
            }

            $indexData[$productId]['category'][] = array_filter($categoryDataRow);
        }

        foreach ($indexData as &$data) {
            $this->getDataExtension($data)
                 ->addCategoryData($data['category'] ?? []);
        }

        return $indexData;
    }
}
