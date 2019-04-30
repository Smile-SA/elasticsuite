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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Build Elasticsearch pipeline aggregation from search request PipelineInterface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
interface PipelineBuilderInterface
{
    /**
     * Build the ES aggregation from a search request pipeline.
     *
     * @param PipelineInterface $pipeline Pipeline to be built.
     *
     * @return array
     */
    public function buildPipeline(PipelineInterface $pipeline);
}
