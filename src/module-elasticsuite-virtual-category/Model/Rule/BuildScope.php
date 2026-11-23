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
 * Scope of the build of a category query requested out of any other query build, shared by all the virtual rules:
 * the queries built meanwhile, whatever the rule building them, are submitted to the build limit.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class BuildScope
{
    /**
     * @var BuildLimitInterface
     */
    private $buildLimit;

    /**
     * @var integer
     */
    private $depth = 0;

    /**
     * Constructor.
     *
     * @param BuildLimitInterface $buildLimit Build limit.
     */
    public function __construct(BuildLimitInterface $buildLimit)
    {
        $this->buildLimit = $buildLimit;
    }

    /**
     * Whether a build is in progress.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->depth > 0;
    }

    /**
     * Open the scope of a build. Nested scopes belong to the enclosing build.
     *
     * @param int $storeId Store id.
     *
     * @return void
     */
    public function open(int $storeId): void
    {
        if ($this->depth++ === 0) {
            $this->buildLimit->start($storeId);
        }
    }

    /**
     * Close the scope of a build.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->depth > 0 && --$this->depth === 0) {
            $this->buildLimit->stop();
        }
    }

    /**
     * Submit a category query about to be built to the build limit.
     *
     * @param int $categoryId Category id.
     *
     * @return void
     * @throws BuildAbortedException When the build limit aborts the build.
     */
    public function beforeBuild(int $categoryId): void
    {
        if ($this->depth > 0) {
            $this->buildLimit->beforeBuild($categoryId);
        }
    }
}
