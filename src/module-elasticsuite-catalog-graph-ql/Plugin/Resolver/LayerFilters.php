<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogGraphQl\Plugin\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Resolver\Context;
use Magento\CatalogGraphQl\Model\Layer\Context as LayerContext;

/**
 * Plugin For Layer Filters resolver :
 * inject previously built search result aggregations into the layer context, to prevent useless additional fetching.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class LayerFilters
{
    /**
     * @var \Magento\CatalogGraphQl\Model\Layer\Context
     */
    private $layerContext;

    /**
     * LayerFilters constructor.
     *
     * @param \Magento\CatalogGraphQl\Model\Layer\Context $layerContext Layer Context
     */
    public function __construct(LayerContext $layerContext)
    {
        $this->layerContext = $layerContext;
    }

    /**
     * Inject previously built search result aggregations into the layer context, to prevent useless additional fetching.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.TooManyParameters)
     *
     * @param ResolverInterface $subject Resolver
     * @param Field             $field   Field
     * @param Context           $context GraphQL context
     * @param ResolveInfo       $info    Resolver Info
     * @param array|null        $value   Value
     * @param array|null        $args    Args
     *
     * @return array The parent method call
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (!empty($value['search_result'])) {
            /** @var \Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult $searchResult */
            $searchResult = $value['search_result'];
            $this->layerContext->getCollectionProvider()->setSearchResults(
                $searchResult->getSearchAggregation(),
                $searchResult->getTotalCount()
            );
        }

        return [$field, $context, $info, $value, $args];
    }
}
