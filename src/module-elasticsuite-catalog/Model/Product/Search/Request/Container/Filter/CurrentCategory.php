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
use Magento\Framework\App\State;
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
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * Current Category filter constructor.
     *
     * @param ContextInterface    $searchContext       Current search context.
     * @param LayerCategoryConfig $layerCategoryConfig Category LayerConfig
     * @param Provider            $filterProvider      Category Filter provider.
     * @param State               $appState            Application state.
     */
    public function __construct(
        ContextInterface $searchContext,
        LayerCategoryConfig $layerCategoryConfig,
        Provider $filterProvider,
        State $appState
    ) {
        $this->searchContext       = $searchContext;
        $this->layerCategoryConfig = $layerCategoryConfig;
        $this->filterProvider      = $filterProvider;
        $this->appState            = $appState;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterQuery()
    {
        $query = null;

        if ($this->isAdminArea()) {
            return null;
        }

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

    /**
     * Check if we are in the admin area
     *
     * @return bool
     */
    private function isAdminArea()
    {
        try {
            return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML;
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            return false;
        }
    }
}
