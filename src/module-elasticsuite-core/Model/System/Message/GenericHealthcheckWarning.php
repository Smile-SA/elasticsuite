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

namespace Smile\ElasticsuiteCore\Model\System\Message;

use Magento\Framework\UrlInterface;
use Magento\Framework\Notification\MessageInterface;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\HealthcheckList;

/**
 * Class GenericWarningAboutClusterMisconfig
 */
class GenericHealthcheckWarning implements MessageInterface
{
    /**
     * Route to Elasticsuite -> Healthcheck page.
     */
    private const ROUTE_ELASTICSUITE_HEALTHCHECK = 'smile_elasticsuite/healthcheck/index';

    /**
     * @var HealthcheckList
     */
    private $healthcheckList;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param HealthcheckList $healthcheckList Health check list object.
     * @param UrlInterface    $urlBuilder      URL builder.
     */
    public function __construct(
        HealthcheckList $healthcheckList,
        UrlInterface $urlBuilder
    ) {
        $this->healthcheckList = $healthcheckList;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayed()
    {
        return $this->getIssueCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        return hash('sha256', 'ELASTICSUITE_GENERIC_WARNING');
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * {@inheritdoc}
     */
    public function getText()
    {
        $issuesCount = $this->getIssueCount();

        // @codingStandardsIgnoreStart
        return __(
            'You have <strong>%1 health checks</strong> in <strong>Warning</strong> state. '
            . 'We invite you to head to the <a href="%2"><strong>Elasticsuite Healthcheck</strong></a> page to get more details and to see how to fix them.',
            $issuesCount,
            $this->getElasticsuiteHealthcheckUrl()
        );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Counts the number of health check issues in an error state.
     *
     * @return int
     */
    private function getIssueCount(): int
    {
        $issuesCount = 0;

        foreach ($this->healthcheckList->getChecks() as $check) {
            if ($check->getStatus() === CheckInterface::WARNING_STATUS) {
                $issuesCount++;
            }
        }

        return $issuesCount;
    }

    /**
     * Retrieve a URL to the Elasticsuite Healthcheck page for more information.
     *
     * @return string
     */
    private function getElasticsuiteHealthcheckUrl(): string
    {
        return $this->urlBuilder->getUrl(self::ROUTE_ELASTICSUITE_HEALTHCHECK);
    }
}
