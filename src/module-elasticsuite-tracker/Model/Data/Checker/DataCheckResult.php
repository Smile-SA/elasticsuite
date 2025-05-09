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

/**
 * Behavioral data check result.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class DataCheckResult
{
    /**
     * @var boolean
     */
    private $hasInvalidata = false;

    /**
     * @var string
     */
    private $description = '';

    /**
     * If any invalid data found.
     *
     * @return bool
     */
    public function hasInvalidData(): bool
    {
        return $this->hasInvalidata;
    }

    /**
     * Set if any invalid data was found.
     *
     * @param bool $hasInvalidData Invalid data found.
     *
     * @return $this
     */
    public function setInvalidData($hasInvalidData): DataCheckResult
    {
        $this->hasInvalidata = $hasInvalidData;

        return $this;
    }

    /**
     * Get invalid data/problem description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set description of the invalid data/problem.
     *
     * @param string $description Description of invalid data/problem.
     *
     * @return $this
     */
    public function setDescription($description): DataCheckResult
    {
        $this->description = $description;

        return $this;
    }
}
