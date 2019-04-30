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

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Pipeline;

use Smile\ElasticsuiteCore\Search\Request\PipelineInterface;

/**
 * Abstract pipeline implementation
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
abstract class AbstractPipeline implements PipelineInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array|string
     */
    private $bucketsPath;

    /**
     * Pipeline constructor
     *
     * @param string       $name        Pipeline name.
     * @param array|string $bucketsPath Pipeline buckets path.
     */
    public function __construct($name, $bucketsPath = null)
    {
        $this->name = $name;
        $this->bucketsPath = $bucketsPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBucketsPath()
    {
        return $this->bucketsPath;
    }

    /**
     * {@inheritDoc}
     */
    public function hasBucketsPath()
    {
        return $this->bucketsPath !== null;
    }
}
