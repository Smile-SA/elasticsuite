<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category\FilterableAttributeList;

use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute as Resource;

/**
 * Category Layered Navigation Filters Save Handler
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
     * @var \Smile\ElasticsuiteCatalog\Model\Category\FilterableAttributeList\Converter
     */
    private $converter;

    /**
     * ReadHandler constructor.
     *
     * @param Resource  $resource  Resource Model
     * @param Converter $converter Converter
     */
    public function __construct(Resource $resource, Converter $converter)
    {
        $this->resource  = $resource;
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        if ($entity->getExtensionAttributes()->getFilterableAttributeList()) {
            $data = $this->converter->fromEntity($entity->getExtensionAttributes()->getFilterableAttributeList());

            $this->resource->saveAttributesData((int) $entity->getId(), $data);
        }

        return $entity;
    }
}
