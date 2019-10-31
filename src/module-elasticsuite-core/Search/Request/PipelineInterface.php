<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Interface for pipeline aggregations
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
interface PipelineInterface
{
    /**
     * Available pipeline types.
     */
    const TYPE_BUCKET_SELECTOR      = 'bucketSelectorPipeline';
    const TYPE_MOVING_FUNCTION      = 'movingFunctionPipeline';

    /**
     * Available gap policies.
     */
    const GAP_POLICY_SKIP           = 'skip';
    const GAP_POLICY_INSERT_ZEROS   = 'insert_zeros';

    /**
     * Get pipeline type.
     *
     * @return string
     */
    public function getType();

    /**
     * Get pipeline name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get (optional) pipeline buckets path.
     *
     * @return array|string
     */
    public function getBucketsPath();
}
