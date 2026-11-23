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

/**
 * Raised by a build limit to abort the build of a category query: the category then returns no products for the
 * given lifetime, and the error is logged.
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class BuildAbortedException extends \RuntimeException
{
    /**
     * @var integer
     */
    private $lifetime;

    /**
     * @var int[]
     */
    private $involvedCategoryIds;

    /**
     * Constructor.
     *
     * @param string $message             Reason of the abort, logged.
     * @param int    $lifetime            Lifetime, in seconds, of the query returning no products.
     * @param int[]  $involvedCategoryIds Categories involved in the build: changing one of them invalidates the query
     *                                    returning no products.
     */
    public function __construct(string $message, int $lifetime, array $involvedCategoryIds = [])
    {
        parent::__construct($message);
        $this->lifetime            = $lifetime;
        $this->involvedCategoryIds = array_map('intval', $involvedCategoryIds);
    }

    /**
     * Lifetime, in seconds, of the query returning no products.
     *
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Categories involved in the build.
     *
     * @return int[]
     */
    public function getInvolvedCategoryIds(): array
    {
        return $this->involvedCategoryIds;
    }
}
