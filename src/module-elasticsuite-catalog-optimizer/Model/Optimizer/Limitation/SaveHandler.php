<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Limitation;

use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;

/**
 * Optimizer Limitation Save Handler
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SaveHandler implements \Magento\Framework\EntityManager\Operation\ExtensionInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation
     */
    private $resource;

    /**
     * SearchTermsSaveHandler constructor.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $resource Resource
     */
    public function __construct(
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        $categoryIds = $this->getCategoryIdsLimitation($entity);
        $queryIds    = $this->getQueryIdsLimitation($entity);

        $limitationData = [];

        foreach ($categoryIds as $categoryId) {
            $limitationData[] = ['category_id' => $categoryId];
        }

        foreach ($queryIds as $queryId) {
            $limitationData[] = ['query_id' => $queryId];
        }

        $this->resource->saveLimitation($entity, $limitationData);

        return $entity;
    }

    /**
     * Retrieve category ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity The optimizer being saved
     *
     * @return array
     */
    private function getCategoryIdsLimitation($entity)
    {
        $searchContainerData = $entity->getData('catalog_view_container');
        $applyTo     = is_array($searchContainerData) ? ((bool) ($searchContainerData['apply_to'] ?? false)) : false;
        $categoryIds = ($applyTo === false) ? [] : $searchContainerData['category_ids'] ?? [];

        return $categoryIds;
    }

    /**
     * Retrieve query ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity The optimizer being saved
     *
     * @return array
     */
    private function getQueryIdsLimitation($entity)
    {
        $searchContainerData = $entity->getData('quick_search_container');
        $applyTo  = is_array($searchContainerData) ? ((bool) ($searchContainerData['apply_to'] ?? false)) : false;
        $queryIds = [];

        if (($applyTo !== false) && (isset($searchContainerData['query_ids']) && !empty($searchContainerData['query_ids']))) {
            $ids = $queryIds = $searchContainerData['query_ids'];

            if (is_array(current($ids))) {
                $queryIds = [];
                foreach ($ids as $queryId) {
                    if (isset($queryId['id'])) {
                        $queryIds[] = (int) $queryId['id'];
                    }
                }
            }
        }

        return $queryIds;
    }
}
