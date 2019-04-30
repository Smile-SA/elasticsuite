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

/**
 * Class BucketSelector
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class BucketSelector extends AbstractPipeline
{
    /**
     * @var string
     */
    private $script;

    /**
     * @var string
     */
    private $gapPolicy;

    /**
     * BucketSelector constructor.
     *
     * @param string       $name        Pipeline name.
     * @param array|string $bucketsPath Pipeline buckets path.
     * @param string       $script      Pipeline script.
     * @param string       $gapPolicy   Pipeline gap policy.
     */
    public function __construct(
        $name,
        $bucketsPath,
        $script,
        $gapPolicy = self::GAP_POLICY_SKIP
    ) {
        parent::__construct($name, $bucketsPath);
        $this->script = $script;
        $this->gapPolicy = $gapPolicy;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return self::TYPE_BUCKET_SELECTOR;
    }

    /**
     * Get pipeline script.
     *
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Get pipeline gap policy.
     *
     * @return string
     */
    public function getGapPolicy()
    {
        return $this->gapPolicy;
    }
}
