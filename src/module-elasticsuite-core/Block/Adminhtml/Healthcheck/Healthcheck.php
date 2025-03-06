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

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Healthcheck;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Notification\MessageInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\HealthcheckList;

/**
 * Class Healthcheck.
 *
 * Block class for displaying Elasticsuite health checks in the Magento Admin panel.
 */
class Healthcheck extends Template
{
    /**
     * HealthcheckList instance to manage and retrieve health checks.
     *
     * @var HealthcheckList
     */
    private $healthcheckList;

    /**
     * Constructor.
     *
     * @param Context         $context         Magento context object for backend blocks.
     * @param HealthcheckList $healthcheckList The health check list object providing health check data.
     * @param array           $data            Additional block data.
     */
    public function __construct(Context $context, HealthcheckList $healthcheckList, array $data = [])
    {
        parent::__construct($context, $data);
        $this->healthcheckList = $healthcheckList;
    }

    /**
     * Retrieve all health checks.
     *
     * Provides an array of health check instances, each implementing the CheckInterface,
     * sorted by their specified order.
     *
     * @return CheckInterface[]
     */
    public function getHealthchecks(): array
    {
        return array_values($this->healthcheckList->getCheckResults());
    }

    /**
     * Retrieve the appropriate CSS class for severity labels.
     *
     * @param int $severity Severity level.
     * @return string
     */
    public function getSeverityCssClass(int $severity): string
    {
        $severityClasses = [
            MessageInterface::SEVERITY_CRITICAL => 'grid-severity-critical',
            MessageInterface::SEVERITY_MAJOR    => 'grid-severity-major',
            MessageInterface::SEVERITY_MINOR    => 'grid-severity-minor',
            MessageInterface::SEVERITY_NOTICE   => 'grid-severity-notice',
        ];

        return $severityClasses[$severity] ?? '';
    }
}
