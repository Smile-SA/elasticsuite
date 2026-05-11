<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Model\Rule\Attribute;

use Smile\ElasticsuiteCatalogOptimizer\Api\Rule\Attribute\OptimizerCollectionFilterInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory;
use Smile\ElasticsuiteCatalogRule\Api\Rule\Attribute\LocationProviderInterface;

/**
 * Catalog Optimizer Location Provider.
 *
 * This provider determines whether a given product attribute
 * is used in Elasticsuite Optimizer rules.
 *
 * The implementation is intentionally decoupled from any specific module
 * (e.g. A/B Campaign) by relying on a pool of collection filters injected
 * via Dependency Injection. This allows external modules to alter the filtering behavior
 * without modifying this class.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class OptimizerLocationProvider implements LocationProviderInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var OptimizerCollectionFilterInterface[]
     */
    private array $filters;

    /**
     * Constructor.
     *
     * @param CollectionFactory                    $collectionFactory Optimizer collection factory.
     * @param OptimizerCollectionFilterInterface[] $filters           Optional filters applied to the collection.
 */
    public function __construct(
        CollectionFactory $collectionFactory,
        array $filters = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function isPresent(string $attribute): bool
    {
        if ($attribute === '') {
            return false;
        }

        $collection = $this->collectionFactory->create();

        // Apply all injected filters.
        foreach ($this->filters as $filter) {
            if ($filter instanceof OptimizerCollectionFilterInterface) {
                $filter->apply($collection);
            }
        }

        // Apply attribute search.
        $collection->addFieldToFilter(
            'rule_condition',
            ['like' => '%"attribute":"' . $attribute . '"%']
        );

        // Lightweight existence check.
        $collection->getSelect()->limit(1);

        return (bool) $collection->getSize();
    }
}
