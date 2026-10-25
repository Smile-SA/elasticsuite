<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Search\OpaqueId;

use Smile\ElasticsuiteCore\Api\Client\OpaqueIdPartProviderInterface;
use Smile\ElasticsuiteVirtualCategory\Model\VirtualCategory\Root;

/**
 * Adds the applied virtual root category (virtual category whose generated subtree is being browsed)
 * to the X-Opaque-Id, e.g. "vcat=49".
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class AppliedRootCategory implements OpaqueIdPartProviderInterface
{
    /**
     * @var Root
     */
    private $virtualCategoryRoot;

    /**
     * Constructor.
     *
     * @param Root $virtualCategoryRoot Virtual category root.
     */
    public function __construct(Root $virtualCategoryRoot)
    {
        $this->virtualCategoryRoot = $virtualCategoryRoot;
    }

    /**
     * {@inheritDoc}
     */
    public function getParts(): array
    {
        $appliedRoot = $this->virtualCategoryRoot->getAppliedRootCategory();

        return ['vcat' => $appliedRoot ? $appliedRoot->getId() : null];
    }
}
