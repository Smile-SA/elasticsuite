<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Event\Mapping;

/**
 * Enforcers interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
interface TypeEnforcerInterface
{
    /**
     * Enforce a given mapping field type on event data.
     *
     * @param array $data Event data.
     *
     * @return array Event data with the mapping enforced.
     */
    public function enforce($data);
}
