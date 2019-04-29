<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Category;

use Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\CollectionFactory as AttributeCollectionFactory;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\AttributeListInterface;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Attributes List used in category navigation queries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributeList implements AttributeListInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * FilterableAttributeList constructor
     *
     * @param AttributeCollectionFactory                          $collectionFactory Attributes Collection Factory
     * @param \Smile\ElasticsuiteCore\Api\Search\ContextInterface $searchContext     Search Context
     */
    public function __construct(
        AttributeCollectionFactory $collectionFactory,
        ContextInterface $searchContext
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchContext     = $searchContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        /** @var $collection \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\FilterableAttribute\Category\Collection */
        $collection = $this->collectionFactory->create();
        $collection->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
            ->setOrder('position', 'ASC');

        $collection->addSetInfo(true);
        $collection->addIsFilterableFilter();
        $collection->setOrder('attribute_id', 'ASC');

        $category = $this->searchContext->getCurrentCategory();
        if ($category && $category->getId()) {
            $collection->setCategory($category);
        }

        $collection->getSelect()->orWhere('attribute_code = "category_ids"');
        $collection->load();

        return $collection->getItems();
    }
}
