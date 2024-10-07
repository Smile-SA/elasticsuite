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

namespace Smile\ElasticsuiteTracker\Model\Data\Checker;

use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;

/**
 * Behavioral data checker interface.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface DataCheckerInterface
{
    /**
     * Perform checks on behavioral data.
     *
     * @param int $storeId Store id.
     *
     * @return DataCheckResult
     */
    public function check($storeId): DataCheckResult;

    /**
     * Returns true if a fixer is available for the invalid data.
     *
     * @return bool
     */
    public function hasDataFixer(): bool;

    /**
     * Returns the invalid data fixer if available.
     *
     * @return DataFixerInterface|null
     */
    public function getDataFixer(): ?DataFixerInterface;
}
