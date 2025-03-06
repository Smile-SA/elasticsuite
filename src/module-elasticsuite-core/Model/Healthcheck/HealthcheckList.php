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

namespace Smile\ElasticsuiteCore\Model\Healthcheck;

use Smile\ElasticsuiteCore\Api\Healthcheck\CheckInterface;
use Smile\ElasticsuiteCore\Model\Healthcheck\CachedHealthcheckFactory;

/**
 * Class HealthcheckList.
 *
 * Manages a list of health checks for the Elasticsuite module.
 */
class HealthcheckList
{
    /** @var string */
    const CACHE_TAG = 'healthcheck_list';

    /** @var CachedHealthcheckFactory */
    private $cachedChecksFactory;

    /**
     * Array of health checks implementing the CheckInterface.
     *
     * @var CheckInterface[]
     */
    private $checks;

    /**
     * Array of executed healthchecks.
     *
     * @var CheckInterface[]
     */
    private $checkResults;

    /**
     * Constructor.
     *
     * @param CachedHealthcheckFactory $cachedChecksFactory Cached healthcheck factory.
     * @param CheckInterface[]         $checks              Array of health checks to be managed by this list.
     */
    public function __construct(
        CachedHealthcheckFactory $cachedChecksFactory,
        array $checks = []
    ) {
        $this->cachedChecksFactory = $cachedChecksFactory;
        $this->checks = $checks;
    }

    /**
     * Retrieve all health checks, sorted by their sort order.
     *
     * Sorts the checks based on the value returned by each check's `getSortOrder` method.
     *
     * @return CheckInterface[] Array of health checks sorted by order.
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function getChecks(): array
    {
        usort($this->checks, function (CheckInterface $a, CheckInterface $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        return $this->checks;
    }

    /**
     * Retrieve all executed health checks, sorted by their sort order.
     *
     * @return CheckInterface[]
     */
    public function getCheckResults(): array
    {
        if (null === $this->checkResults) {
            foreach ($this->getChecks() as $check) {
                $this->checkResults[$check->getIdentifier()] = $this->cachedChecksFactory->create([
                    'identifier' => $check->getIdentifier(),
                    'status' => $check->getStatus(),
                    'description' => $check->getDescription(),
                    'severity'    => $check->getSeverity(),
                ]);
            }
        }

        return $this->checkResults;
    }
}
