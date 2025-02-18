<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Healthcheck;

/**
 * Health CheckInterface.
 */
interface CheckInterface
{
    /**
     * Status indicating that the health check has passed successfully.
     */
    const STATUS_PASSED = 'success';

    /**
     * Status indicating that the health check has detected a potential issue.
     */
    const STATUS_FAILED = 'warning';

    /**
     * Retrieve the unique identifier for the health check.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Retrieve the description of the health check.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Retrieve the status of the health check.
     * Expected values: 'success', 'warning'.
     *
     * @return string
     */
    public function getStatus(): string;
}
