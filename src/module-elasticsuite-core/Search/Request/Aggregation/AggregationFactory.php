<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

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
    private $factories;

    /**
     * Constructor.
     *
     * @param array $factories Aggregation factories by type.
     */
    public function __construct($factories = [])
    {
        $this->factories = $factories;
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
            throw new \LogicException("No factory found for aggregation of type {$bucketType}");
        }

        return $this->factories[$bucketType]->create($bucketParams);
    }
}
