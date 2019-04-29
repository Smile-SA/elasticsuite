<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Save Handler for filterable attributes by category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute
     */
    private $resource;

    /**
     * SaveHandler constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute $resource Resource Model
     */
    public function __construct(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getId() && $entity->getFacetConfig() && $entity->getFacetConfigOrder()) {
            $position  = [];
            $data      = $entity->getFacetConfig();
            $sortOrder = $entity->getFacetConfigOrder();

            foreach ($data as $key => &$item) {
                $item['position'] = isset($sortOrder[$key]) ? $sortOrder[$key] : $item['position'];
                $position[] = (int) $item['position'];
            }
            array_multisort($position, SORT_ASC, $data);

            $this->resource->saveAttributesData($entity->getId(), $data);
        }

        return $entity;
    }
}
