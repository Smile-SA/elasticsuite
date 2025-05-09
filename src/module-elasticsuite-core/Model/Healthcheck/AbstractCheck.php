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
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;

/**
 * Abstract class for health checks in Elasticsuite module.
 *
 * This class provides a base implementation for common health check functionality.
 */
abstract class AbstractCheck implements CheckInterface
{
    /**
     * URL builder instance to generate admin URLs.
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Sort order of the health check.
     *
     * Lower values indicate higher priority in display order.
     *
     * @var integer
     */
    protected $sortOrder;

    /**
     * Severity level of the health check.
     *
     * @var integer
     */
    protected int $severity;

    /**
     * Constructor.
     *
     * @param UrlInterface $urlBuilder URL builder for generating links in the admin panel.
     * @param int          $sortOrder  Sort order for the check (default: 10000).
     * @param int          $severity   Severity level.
     */
    public function __construct(
        UrlInterface $urlBuilder,
        int $sortOrder = 10000,
        int $severity = MessageInterface::SEVERITY_NOTICE
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->sortOrder  = $sortOrder;
        $this->severity   = $severity;
    }

    /**
     * Retrieve the sort order for this health check.
     *
     * The sort order determines the display priority of the check.
     * Checks with lower values appear first.
     *
     * @return int Sort order value.
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityLabel(): string
    {
        return __(CheckInterface::SEVERITY_LABELS[$this->severity] ?? 'N/A');
    }
}
