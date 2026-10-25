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
 * Provides additional parts of the X-Opaque-Id describing the current context (e.g. the current category).
 * Part providers are injected into the X-Opaque-Id provider through its "partProviders" constructor argument.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
interface OpaqueIdPartProviderInterface
{
    /**
     * Return the parts to add to the X-Opaque-Id, as key => value pairs (e.g. ['cat' => 4]).
     * Null or empty values are ignored.
     *
     * @return array
     */
    public function getParts(): array;
}
