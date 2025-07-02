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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface to be implemented by fixer models that might need to report their progress
 * in an independent and specific manner (output messages, custom progress bar, etc) in a dedicated output section.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 */
interface OutputAwareInterface
{
    /**
     * Set the outputs interface to report progress.
     *
     * @param OutputInterface $output Output interface.
     *
     * @return self
     */
    public function setOutput(OutputInterface $output): self;

    /**
     * Returns the output interface.
     *
     * @return OutputInterface|null
     */
    public function getOutput(): ?OutputInterface;

    /**
     * Returns true if an output interface has been set.
     *
     * @return bool
     */
    public function hasOutput(): bool;
}
