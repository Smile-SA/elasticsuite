<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Model\Layer;

use Magento\Catalog\Model\Category as Category;
use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\Search\AggregationInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Custom Layer Collection Provider for GraphQL.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CollectionProvider implements ItemCollectionProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * Collection Provider constructor.
     *
     * @param CollectionProcessorInterface $collectionProcessor Collection Processor
     * @param CollectionFactory            $collectionFactory   Collection Factory
     */
    public function __construct(
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $collectionFactory
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->collectionFactory   = $collectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection(Category $category): Collection
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();
        }

        return $this->collection;
    }

    /**
     * Inject current search result aggregations into the collection and overwrite any precedence.
     *
     * @param AggregationInterface $aggregations Search Engine aggregations
     * @param int                  $resultSize   Search Engine results size
     *
     * @return $this
     */
    public function setSearchResults(AggregationInterface $aggregations, $resultSize = 0)
    {
        $this->collection = $this->collectionFactory->create();
        $this->collection->setSearchResults($aggregations, $resultSize);

        return $this;
    }
}
