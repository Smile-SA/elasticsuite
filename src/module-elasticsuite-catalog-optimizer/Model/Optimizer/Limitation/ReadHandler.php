<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
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
            $this->getCategoryLimitation($entity, $searchContainers);
            $this->getSearchQueryLimitation($entity, $searchContainers);
        }

        return $entity;
    }

    /**
     * Retrieve category ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity           The optimizer being saved
     * @param array              $searchContainers Search Containers data for the current optimizer.
     *
     * @return array
     */
    private function getCategoryLimitation($entity, $searchContainers)
    {
        $applyTo = (bool) ($searchContainers['catalog_view_container'] ?? false);

        if ($applyTo) {
            $containerData = ['apply_to' => (int) true];
            $categoryIds   = $this->resource->getCategoryIdsByOptimizer($entity);
            if (!empty($categoryIds)) {
                $containerData['category_ids'] = $categoryIds;
            }
            $entity->setData('catalog_view_container', $containerData);
        }
    }

    /**
     * Retrieve query ids limitation for the current optimizer, if any.
     *
     * @param OptimizerInterface $entity           The optimizer being saved
     * @param array              $searchContainers Search Containers data for the current optimizer.
     *
     * @return array
     */
    private function getSearchQueryLimitation($entity, $searchContainers)
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

        return $entity;
    }
}
