<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer;

/**
 * Behavioral data fixer interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface DataFixerInterface
{
    /**
     * Fix invalid data in a given behavioral index for a given store.
     * Returns true if the data was fixed.
     *
     * @param int $storeId Store id.
     *
     * @return bool
     */
    public function fixInvalidData(int $storeId): bool;
}
