<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule;

use Smile\ElasticsuiteVirtualCategory\Api\Rule\BuildLimitInterface;

/**
 * No limit of the category queries built to compute a category query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class NoBuildLimit implements BuildLimitInterface
{
    /**
     * {@inheritDoc}
     */
    public function start(int $storeId): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function beforeBuild(int $categoryId): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): void
    {
    }
}
