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
    /** @var int */
    const FIX_FAILURE   = 0;

    /** @var int */
    const FIX_COMPLETE  = 1;

    /** @var int */
    const FIX_PARTIAL   = 2;

    /**
     * Fix invalid data in a given behavioral index for a given store.
     * Returns a code defining if all data was fixed, if some partial was fixed or if an error occurred.
     *
     * @param int $storeId Store id.
     *
     * @return int
     */
    public function fixInvalidData(int $storeId): int;
}
