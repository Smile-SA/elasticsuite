<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Product\Search\Request\Container\Filter;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\Container\FilterInterface;
use Smile\ElasticsuiteVirtualCategory\Model\Category\Filter\Provider;

/**
 * Current Category filter implementation
 *
 * @category Smile
 * @package  Smile\ElasticSuiteCatalog
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class CurrentCategory implements FilterInterface
{
    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * @var LayerCategoryConfig
     */
    private $layerCategoryConfig;

    /**
     * @var Provider
     */
    private $filterProvider;

    /**
     * Current Category filter constructor.
     *
     * @param ContextInterface    $searchContext       Current search context.
     * @param LayerCategoryConfig $layerCategoryConfig Category LayerConfig
     * @param Provider            $filterProvider      Category Filter provider.
     */
    public function __construct(
        ContextInterface $searchContext,
        LayerCategoryConfig $layerCategoryConfig,
        Provider $filterProvider
    ) {
        $this->searchContext       = $searchContext;
        $this->layerCategoryConfig = $layerCategoryConfig;
        $this->filterProvider      = $filterProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery()
    {
        $query = null;

        if (false === $this->isDisplayCategoryFilter() && $this->searchContext->getCurrentCategory()) {
            $query = $this->filterProvider->getQueryFilter($this->searchContext->getCurrentCategory());
        }

        return $query;
    }

    /**
     * Get config value for 'display category filter' option
     *
     * @return bool
     */
    private function isDisplayCategoryFilter()
    {
        return $this->layerCategoryConfig->isCategoryFilterVisibleInLayerNavigation();
    }
}
