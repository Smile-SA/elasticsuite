<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Model\Rule;

use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContext\ProviderInterface;
use Smile\ElasticsuiteCatalogRule\Api\Rule\QueryContextResolverInterface;

/**
 * Values of the contexts rule search queries can depend on, given by the providers declared by context code.
 *
 * Values are normalized as strings: booleans as 0/1, arrays as their sorted unique values separated by commas, and
 * values longer than 64 characters as a hash.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class QueryContextResolver implements QueryContextResolverInterface
{
    /**
     * Maximum length of a value kept as is.
     */
    const MAX_VALUE_LENGTH = 64;

    /**
     * @var ProviderInterface[]
     */
    private $providers;

    /**
     * Constructor.
     *
     * @param ProviderInterface[] $providers Context value providers, by context code.
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getValues(array $codes): array
    {
        $values = [];

        foreach ($codes as $code) {
            if (!isset($this->providers[$code])) {
                throw new \LogicException(sprintf('No provider is declared for the rule query context "%s".', $code));
            }
            $values[$code] = $this->normalize($this->providers[$code]->getValue());
        }

        return $values;
    }

    /**
     * Normalize a context value as a string.
     *
     * @param string|int|bool|array $value Context value.
     *
     * @return string
     */
    private function normalize($value): string
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }

        if (is_array($value)) {
            $value = array_unique(array_map('strval', $value));
            sort($value);
            $value = implode(',', $value);
        }

        $value = (string) $value;

        return strlen($value) > self::MAX_VALUE_LENGTH ? '#' . substr(sha1($value), 0, 12) : $value;
    }
}
