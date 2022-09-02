<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Botis <botis@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation\Pipeline;

/**
 * Class MaxBucket
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class MaxBucket extends AbstractPipeline
{
    /**
     * @var string
     */
    private $gapPolicy;

    /**
     * @var string
     */
    private $format;

    /**
     * MaxBucket constructor.
     *
     * @param string       $name        Pipeline name.
     * @param array|string $bucketsPath Pipeline buckets path.
     * @param string       $gapPolicy   Pipeline gap policy.
     * @param string       $format      Pipeline format.
     */
    public function __construct(
        $name,
        $bucketsPath,
        $gapPolicy = self::GAP_POLICY_SKIP,
        $format = ''
    ) {
        parent::__construct($name, $bucketsPath);
        $this->gapPolicy = $gapPolicy;
        $this->format = $format;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return self::TYPE_MAX_BUCKET;
    }

    /**
     * Get pipeline format.
     *
     * @return string|null
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Get pipeline gap policy.
     *
     * @return string
     */
    public function getGapPolicy(): string
    {
        return $this->gapPolicy;
    }
}
