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

use Magento\Framework\Notification\MessageInterface;

/**
 * Health CheckInterface.
 */
interface CheckInterface
{
    /**
     * Mapping of severity levels to their corresponding human-readable labels.
     *
     * @var array<int, string>
     */
    public const SEVERITY_LABELS = [
        MessageInterface::SEVERITY_CRITICAL => 'Critical',
        MessageInterface::SEVERITY_MAJOR    => 'Error',
        MessageInterface::SEVERITY_MINOR    => 'Warning',
        MessageInterface::SEVERITY_NOTICE   => 'Notice',
    ];

    /**
     * Status indicating that the health check has passed successfully.
     */
    const STATUS_PASSED = 'passed';

    /**
     * Status indicating that the health check has detected a potential issue.
     */
    const STATUS_FAILED = 'failed';

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
     * Expected values: 'passed', 'failed'.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Retrieve the severity level of the health check.
     *
     * @return int Severity level.
     */
    public function getSeverity(): int;

    /**
     * Retrieve the severity label as a translated string.
     *
     * @return string Translated severity label.
     */
    public function getSeverityLabel(): string;
}
