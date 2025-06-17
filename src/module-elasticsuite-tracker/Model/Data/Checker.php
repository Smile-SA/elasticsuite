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
use Smile\ElasticsuiteTracker\Model\Data\Fixer\DataFixerInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\OutputAwareInterface;
use Smile\ElasticsuiteTracker\Model\Data\Fixer\ProgressIndicatorAwareInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param int                    $storeId           Store id.
     * @param ProgressIndicator|null $progressIndicator Global progress indicator.
     * @param OutputInterface|null   $output            Output interface.
     *
     * @return string[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function checkAndFixData(int $storeId, ?ProgressIndicator $progressIndicator = null, ?OutputInterface $output = null): array
    {
        $data = [];

        foreach ($this->checkers as &$checker) {
            $checkResult = $checker->check($storeId);
            if ($checkResult->hasInvalidData()) {
                $checkDescription = $checkResult->getDescription();
                $report = sprintf('Unfixed: %s', $checkDescription);
                if ($checker->hasDataFixer()) {
                    $dataFixer = $checker->getDataFixer();
                    if ($progressIndicator && ($dataFixer instanceof ProgressIndicatorAwareInterface)) {
                        $dataFixer->setProgressIndicator($progressIndicator);
                    }
                    if ($output && ($dataFixer instanceof OutputAwareInterface)) {
                        $dataFixer->setOutput($output);
                    }
                    $fixerResult = $checker->getDataFixer()->fixInvalidData($storeId);
                    $status = 'Failure to fix';
                    if ($fixerResult === DataFixerInterface::FIX_COMPLETE) {
                        $status = 'Fixed';
                    }
                    if ($fixerResult === DataFixerInterface::FIX_PARTIAL) {
                        $status = 'Partial fix';
                    }
                    $report = sprintf('[%s] %s', $status, $checkDescription);
                }
                $data[] = $report;
            }
        }

        return $data;
    }
}
