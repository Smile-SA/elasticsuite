<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Block\Adminhtml\Healthcheck;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\HealthcheckList;

/**
 * Elasticsuite menu decorator block.
 * Adds a failed/warning healthchecks counter next to Elasticsuite elements in the menu.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class MenuDecorator extends Template
{
    /** @var HealthcheckList */
    private $healthcheckList;

    /** @var integer */
    private $issuesCount;

    /**
     * Constructor.
     *
     * @param HealthcheckList      $healthcheckList Healthchecks list.
     * @param Template\Context     $context         Template context.
     * @param array                $data            Data.
     * @param JsonHelper|null      $jsonHelper      Json helper.
     * @param DirectoryHelper|null $directoryHelper Directory helper.
     */
    public function __construct(
        HealthcheckList $healthcheckList,
        Template\Context $context,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->healthcheckList = $healthcheckList;
    }

    /**
     * Returns true if the menu decoration should happen.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getIssuesCount() > 0;
    }

    /**
     * Returns the number of failed tests.
     *
     * @return int
     */
    public function getIssuesCount()
    {
        if (null === $this->issuesCount) {
            $this->issuesCount = 0;

            foreach ($this->healthcheckList->getChecks() as $check) {
                if ($check->getStatus() === CheckInterface::STATUS_FAILED) {
                    $this->issuesCount++;
                }
            }
        }

        return $this->issuesCount;
    }

    /**
     * {@inheritDoc}
     */
    protected function getCacheLifetime()
    {
        // Very short cache TTL until a proper cache mechanism is set up at the healthcheck list level.
        return 60;
    }
}
