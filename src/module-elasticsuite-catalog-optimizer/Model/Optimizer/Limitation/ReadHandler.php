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
 * Optimizer Limitation Read Handler
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReadHandler implements \Magento\Framework\EntityManager\Operation\ExtensionInterface
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
        if ($entity->getId()) {
            $searchContainers = $entity->getResource()->getSearchContainersFromOptimizerId($entity->getId());
            $this->setCategoryLimitation($entity, $searchContainers);
            $this->setSearchQueryLimitation($entity, $searchContainers);
        }

        return $entity;
    }

    /**
     * Retrieve and set category ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity           The optimizer being read
     * @param array              $searchContainers Search Containers data for the current optimizer.
     *
     * @return void
     */
    private function setCategoryLimitation($entity, $searchContainers)
    {
        $applyTo = (int) ($searchContainers['catalog_view_container'] ?? 0);

        if ($applyTo) {
            $containerData = ['apply_to' => $applyTo];
            $categoryIds   = $this->resource->getCategoryIdsByOptimizer($entity);
            if (!empty($categoryIds)) {
                $containerData['category_ids'] = $categoryIds;
            }
            $entity->setData('catalog_view_container', $containerData);
        }
    }

    /**
     * Retrieve and set query ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity           The optimizer being read
     * @param array              $searchContainers Search Containers data for the current optimizer.
     *
     * @return void
     */
    private function setSearchQueryLimitation($entity, $searchContainers)
    {
        $applyTo = (bool) ($searchContainers['quick_search_container'] ?? ($searchContainers['catalog_product_autocomplete'] ?? false));

        if ($applyTo) {
            $containerData = ['apply_to' => (int) true];
            $queryIds      = $this->resource->getQueryIdsByOptimizer($entity);

            if (!empty($queryIds)) {
                $containerData['query_ids'] = $queryIds;
            }
            $entity->setData('quick_search_container', $containerData);
        }
    }
}
