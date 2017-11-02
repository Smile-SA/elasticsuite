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

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttributeList as Resource;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory;

/**
 * Category Layered Navigation Filters Read Handler
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReadHandler implements ExtensionInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttributeList
     */
    private $resource;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\Category\FilterableAttributeList\Converter
     */
    private $converter;

    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ReadHandler constructor.
     *
     * @param Resource                   $resource          Resource Model
     * @param Converter                  $converter         Converter
     * @param ExtensionAttributesFactory $extensionFactory  Extension Attributes Factory
     * @param CollectionFactory          $collectionFactory Filterable Attributes List Collection Factory
     */
    public function __construct(
        Resource $resource,
        Converter $converter,
        ExtensionAttributesFactory $extensionFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource                   = $resource;
        $this->converter                  = $converter;
        $this->extensionAttributesFactory = $extensionFactory;
        $this->collectionFactory          = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity, $arguments = [])
    {
        $entity               = $this->loadExtensionAttributes($entity);
        $attributesCollection = $this->collectionFactory->create(['category' => $entity]);

        $attributesCollection
            ->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->addStoreLabel($entity->getStore()->getId())
            ->setOrder('position', 'ASC');

        $entity->getExtensionAttributes()->setFilterableAttributeList($attributesCollection->getItems());
        $entity->setFilterableAttributeList($attributesCollection->getItems());

        return $entity;
    }

    /**
     * Check if extension attributes are loaded and exists. Instantiate them if needed.
     *
     * @see https://github.com/magento/magento2/issues/10847
     *
     * @param CategoryInterface $category Category
     *
     * @return \Magento\Catalog\Api\Data\CategoryExtensionInterface
     */
    private function loadExtensionAttributes($category)
    {
        $extensionAttributes = $category->getExtensionAttributes();
        if (null === $extensionAttributes) {
            /** @var \Magento\Catalog\Api\Data\CategoryExtensionInterface $extensionAttributes */
            $extensionAttributes = $this->extensionAttributesFactory->create(CategoryInterface::class);
            $category->setExtensionAttributes($extensionAttributes);
        }

        return $category;
    }
}
