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

/**
 * Pipeline Processor Provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
interface PipelineProcessorProviderInterface
{
    /**
     * Get Pipeline processors by index identifier.
     *
     * @param string $indexIdentifier Index Identifier
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineProcessorInterface[]
     */
    public function getProcessors(string $indexIdentifier) : array;
}
