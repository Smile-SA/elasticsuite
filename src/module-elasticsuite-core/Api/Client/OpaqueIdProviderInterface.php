<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Client;

/**
 * Provides the X-Opaque-Id value identifying the origin (area, action, command, cron job, store...)
 * of the requests sent to the search engine, as visible in its slow logs and tasks API.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface OpaqueIdProviderInterface
{
    /**
     * Return the opaque id for the current context, or null if the feature is disabled or nothing is known.
     * Built as "key=value" pairs separated by ";" (e.g. "area=frontend;act=catalog_category_view;str=default").
     *
     * @param array $details Additional key/value pairs to append (e.g. ['cnt' => 'catalog_view_container']).
     *
     * @return string|null
     */
    public function getOpaqueId(array $details = []): ?string;

    /**
     * Set the code of the cron job currently running (e.g. "index/indexer_reindex_all_invalid"), or null to unset it.
     *
     * @param string|null $jobName Cron job code, prefixed by its group.
     *
     * @return void
     */
    public function setCronJob(?string $jobName): void;
}
