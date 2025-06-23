<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\Elasticsuite
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteTracker\Model\Data\Fixer;

use Symfony\Component\Console\Helper\ProgressIndicator;

/**
 * Interface to be implemented by fixer models that have long running step-based processes
 * and which might need to report that they are doing something..
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
interface ProgressIndicatorAwareInterface
{
    /**
     * Set the global progress indicator to report progress.
     *
     * @param ProgressIndicator $progressIndicator Progress indicator.
     *
     * @return self
     */
    public function setProgressIndicator(ProgressIndicator $progressIndicator): ProgressIndicatorAwareInterface;

    /**
     * Returns the progress indicator, if set.
     *
     * @return ProgressIndicator|null
     */
    public function getProgressIndicator(): ?ProgressIndicator;

    /**
     * Returns true if a progress indicator has been set.
     *
     * @return bool
     */
    public function hasProgressIndicator(): bool;
}
