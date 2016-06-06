<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory for search request aggregation buckets.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AggregationFactory
{
    /**
     * @var array
     */
    private $factories = [
        BucketInterface::TYPE_TERM        => 'Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\TermFactory',
        BucketInterface::TYPE_HISTOGRAM   => 'Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\HistogramFactory',
        BucketInterface::TYPE_QUERY_GROUP => 'Smile\ElasticsuiteCore\Search\Request\Aggregation\Bucket\QueryGroupFactory',
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface $objectManager Object manager instance.
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create a new bucket from it's type and params.
     *
     * @param string $bucketType   Bucket type (must be a valid bucket type defined into the factories array).
     * @param array  $bucketParams Bucket constructor params.
     *
     * @return BucketInterface
     */
    public function create($bucketType, $bucketParams)
    {
        if (!isset($this->factories[$bucketType])) {
            throw new \LogicException("No factory found for query of type {$bucketType}");
        }

        $factory = $this->objectManager->get($this->factories[$bucketType]);

        return $factory->create($bucketParams);
    }
}
