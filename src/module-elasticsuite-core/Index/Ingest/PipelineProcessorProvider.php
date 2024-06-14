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

namespace Smile\ElasticsuiteCore\Index\Ingest;

use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineProcessorProviderInterface;

/**
 * Pipeline Processor Provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class PipelineProcessorProvider implements PipelineProcessorProviderInterface
{
    /**
     * @var array
     */
    private $processors;

    /**
     * PipelineProcessorProvider constructor.
     *
     * @param array $processors The processors (from DI).
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcessors(string $indexIdentifier) : array
    {
        $processors = [];

        foreach ($this->processors[$indexIdentifier] ?? [] as $name => $processor) {
            if (!$processor instanceof \Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineProcessorInterface) {
                throw new \InvalidArgumentException(
                    'Processor must implement ' . \Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineProcessorInterface::class
                );
            }
            $processors[$name] = $processor;
        }

        return $processors;
    }
}
