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

use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineProcessorProviderInterface;
use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineInterfaceFactory;
use Smile\ElasticsuiteCore\Api\Client\ClientInterface;

/**
 * Pipeline Manager
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class PipelineManager implements PipelineManagerInterface
{
    /**
     * @param ClientInterface                    $client            Elasticsuite Client
     * @param PipelineProcessorProviderInterface $processorProvider Processor Provider
     * @param PipelineInterfaceFactory           $pipelineFactory   Processor Provider
     * @param string                             $pipelinePrefix    Pipeline Prefix
     */
    public function __construct(
        private ClientInterface $client,
        private PipelineProcessorProviderInterface $processorProvider,
        private PipelineInterfaceFactory $pipelineFactory,
        private string $pipelinePrefix = "es-llm-pipeline-"
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $name, string $description, array $processors): ?Pipeline
    {
        $result = null;

        if (!empty($processors)) {
            $query = [
                'id'   => $name,
                'body' => [
                    'description' => $description,
                    'processors'  => $processors,
                ],
            ];
            $this->client->putPipeline($query);

            $result = $this->pipelineFactory->create(['name' => $name, 'description' => $description, 'processors' => $processors]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): ?Pipeline
    {
        try {
            $data        = $this->client->getPipeline($name);
            $description = '';
            if (\array_key_exists('description', $data[$name])) {
                $description = $data[$name]['description'];
            }

            return $this->pipelineFactory->create(
                ['name' => $name, 'description' => $description, 'processors' => $data[$name]['processors']]
            );
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception
     */
    public function createByIndexIdentifier(string $identifier): ?Pipeline
    {
        $pipelineName = $this->pipelinePrefix . $identifier;
        $processors   = [];

        foreach ($this->processorProvider->getProcessors($identifier) as $processor) {
            $processors = array_merge_recursive($processors, $processor->getProcessors());
        }

        return $this->create($pipelineName, $pipelineName, $processors);
    }
}
