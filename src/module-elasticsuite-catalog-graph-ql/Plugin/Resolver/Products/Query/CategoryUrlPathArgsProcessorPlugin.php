<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Plugin\Resolver\Products\Query;

/* Not using the exact type since it might not exist yet in (some? all?) 2.4.6 version(s) */
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;

/**
 * Category Url Path Argument processor plugin.
 * Allows the processor to be usable on more than just the 'products' query
 * and allow a filter 'eq' on 'catalog_url_path' to be correctly interpreted as a catalog navigation query
 * and proper Elasticsuite searchContext to be set up.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 */
class CategoryUrlPathArgsProcessorPlugin
{
    /**
     * @var array
     */
    private $queries;

    /**
     * Constructor.
     *
     * @param array $queries GraphQL queries for which to make sure the category url path processor is also applied.
     */
    public function __construct(array $queries = ['viewMoreFilter'])
    {
        $this->queries = $queries;
    }

    /**
     * Before plugin - intercepts the call to the process method and masquerade valid queries as 'products'.
     *
     * @param ArgumentsProcessorInterface $subject   Category url path query argument processor.
     * @param string                      $fieldName GraphQl query name.
     * @param array                       $args      Arguments being processed.
     *
     * @return array Original or modified arguments.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeProcess(
        ArgumentsProcessorInterface $subject,
        string $fieldName,
        array $args
    ): array {
        if (in_array($fieldName, $this->queries, true)) {
            return ['products', $args];
        }

        return [$fieldName, $args];
    }
}
