<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteAnalytics
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteAnalytics\Model\Report;

/**
 * Post processor interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteAnalytics
 */
interface PostProcessorInterface
{
    /**
     * Post process response data
     *
     * @param array $data Data to post-process
     * @return array
     */
    public function postProcessResponse($data);
}
