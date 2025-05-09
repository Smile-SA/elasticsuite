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

namespace Smile\ElasticsuiteTracker\Model\Data;

use Smile\ElasticsuiteTracker\Model\Data\Checker\DataCheckerInterface;

/**
 * Behavioral data checker.
 * Relies on checkers to check behavioral data for a given store.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Checker
{
    /**
     * @var DataCheckerInterface[]
     */
    private array $checkers;

    /**
     * Constructor.
     *
     * @param DataCheckerInterface[] $checkers Data checkers.
     */
    public function __construct(array $checkers = [])
    {
        $this->checkers = $checkers;
    }

    /**
     * Check behavioral data.
     *
     * @param int $storeId Store id.
     *
     * @return string[]
     */
    public function checkData(int $storeId): array
    {
        $data = [];

        foreach ($this->checkers as &$checker) {
            $checkResult = $checker->check($storeId);
            if ($checkResult->hasInvalidData()) {
                $data[] = $checkResult->getDescription();
            }
        }

        return $data;
    }

    /**
     * Check and fix behavioral data when possible.
     *
     * @param int $storeId Store id.
     *
     * @return string[]
     */
    public function checkAndFixData(int $storeId): array
    {
        $data = [];

        foreach ($this->checkers as &$checker) {
            $checkResult = $checker->check($storeId);
            if ($checkResult->hasInvalidData()) {
                $status = sprintf("Unfixed: %s", $checkResult->getDescription());
                if ($checker->hasDataFixer()) {
                    if ($checker->getDataFixer()->fixInvalidData($storeId)) {
                        $status = sprintf("Fixed %s", $checkResult->getDescription());
                    }
                }
                $data[] = $status;
            }
        }

        return $data;
    }
}
