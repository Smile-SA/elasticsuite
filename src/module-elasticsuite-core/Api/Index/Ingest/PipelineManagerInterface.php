<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Pierre Gauthier <pierre.gauthier@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Index\Ingest;

use Smile\ElasticsuiteCore\Index\Ingest\Pipeline;

/**
 * Pipeline Manager
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
interface PipelineManagerInterface
{
    /**
     * Create ingest pipeline.
     *
     * @param string $name        name pipeline
     * @param string $description description pipeline
     * @param array  $processors  processors pipeline
     */
    public function create(string $name, string $description, array $processors): ?Pipeline;

    /**
     * Get Pipeline by name.
     *
     * @param string $name Pipeline name.
     */
    public function get(string $name): ?Pipeline;

    /**
     * Create Pipeline by index identifier.
     *
     * @param string $identifier Index identifier
     */
    public function createByIndexIdentifier(string $identifier): ?Pipeline;
}
