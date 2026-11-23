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

namespace Smile\ElasticsuiteVirtualCategory\Api\Rule;

use Smile\ElasticsuiteVirtualCategory\Model\Rule\BuildAbortedException;

/**
 * Limit of the category queries built to compute the query of a category requested out of any other query build
 * (eg. for its own page). Intricate virtual category rules, typically large cycles of categories referencing each
 * other, can require a number of sub-queries growing exponentially: a limit can abort such a build.
 * There is no limit by default.
 *
 * @api
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface BuildLimitInterface
{
    /**
     * End of the name of the query returning no products of a category whose build was aborted (see
     * BuildAbortedException), after a label of the category (eg. "(1/2/42) category [Shoes]:42 =>
     * virtual_cat_rule_build_aborted"): it identifies that query, or the queries embedding it, as incomplete.
     */
    public const ABORTED_QUERY_NAME = 'virtual_cat_rule_build_aborted';

    /**
     * Start of the build of a category query requested out of any other query build.
     *
     * @param int $storeId Store id.
     *
     * @return void
     */
    public function start(int $storeId): void;

    /**
     * A category query is about to be built (ie. not served from a cache) within the current build.
     *
     * @param int $categoryId Category id.
     *
     * @return void
     * @throws BuildAbortedException To abort the current build.
     */
    public function beforeBuild(int $categoryId): void;

    /**
     * End of the current build, whether completed or aborted.
     *
     * @return void
     */
    public function stop(): void;
}
