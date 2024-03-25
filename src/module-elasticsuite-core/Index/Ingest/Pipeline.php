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

use Smile\ElasticsuiteCore\Api\Index\Ingest\PipelineInterface;

/**
 * Ingest Pipeline Implementation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Pierre Gauthier <pierre.gauthier@smile.fr>
 */
class Pipeline implements PipelineInterface
{
    /**
     * @param string $name        Pipeline name
     * @param string $description Pipeline description
     * @param array  $processors  Pipeline processors
     */
    public function __construct(
        protected string $name,
        protected string $description,
        protected array $processors
    ) {
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get processors
     *
     * @return array
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }
}
