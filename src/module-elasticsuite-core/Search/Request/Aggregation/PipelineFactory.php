<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Factory for search request pipeline aggregations.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class PipelineFactory
{
    /**
     * @var array
     */
    private $factories;

    /**
     * Constructor.
     *
     * @param array $factories Pipeline aggregation factories by type.
     */
    public function __construct($factories = [])
    {
        $this->factories = $factories;
    }

    /**
     * Create a new pipeline from its type and params.
     *
     * @param string $pipelineType   Pipeline type (must be a valid pipeline type defined into the factories array).
     * @param array  $pipelineParams Pipeline constructor params.
     *
     * @return PipelineInterface
     */
    public function create($pipelineType, $pipelineParams)
    {
        if (!isset($this->factories[$pipelineType])) {
            throw new \LogicException("No factory found for pipeline aggregation of type {$pipelineType}");
        }

        return $this->factories[$pipelineType]->create($pipelineParams);
    }
}
