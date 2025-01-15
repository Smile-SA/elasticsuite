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

/**
 * Class HealthcheckList.
 *
 * Manages a list of health checks for the Elasticsuite module.
 */
class HealthcheckList
{
    /**
     * Array of health checks implementing the CheckInterface.
     *
     * @var CheckInterface[]
     */
    private $checks;

    /**
     * Constructor.
     *
     * @param CheckInterface[] $checks Array of health checks to be managed by this list.
     */
    public function __construct(array $checks = [])
    {
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
}
