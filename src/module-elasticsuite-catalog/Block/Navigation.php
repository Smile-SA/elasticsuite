<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block;

/**
 * Custom implementation of the navigation block to apply facet coverage rate.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Navigation extends \Magento\LayeredNavigation\Block\Navigation
{
    const DEFAULT_EXPANDED_FACETS_COUNT_CONFIG_XML_PATH = 'smile_elasticsuite_catalogsearch_settings/catalogsearch/expanded_facets';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * @var string[]
     */
    private $inlineLayouts = ['1column'];

    /**
     * @var string|NULL
     */
    private $pageLayout;

    /**
     * Navigation constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context       $context        Application context
     * @param \Magento\Catalog\Model\Layer\Resolver                  $layerResolver  Layer Resolver
     * @param \Magento\Catalog\Model\Layer\FilterList                $filterList     Filter List
     * @param \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag Visibility Flag
     * @param \Magento\Framework\ObjectManagerInterface              $objectManager  Object Manager
     * @param \Magento\Framework\Module\Manager                      $moduleManager  Module Manager
     * @param array                                                  $data           Block Data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Model\Layer\FilterList $filterList,
        \Magento\Catalog\Model\Layer\AvailabilityFlagInterface $visibilityFlag,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data
    ) {
        parent::__construct($context, $layerResolver, $filterList, $visibilityFlag, $data);
        $this->pageLayout         = $context->getPageConfig()->getPageLayout() ?: $this->getLayout()->getUpdate()->getPageLayout();
        $this->objectManager      = $objectManager;
        $this->moduleManager      = $moduleManager;
    }

    /**
     * Check if we can show this block.
     * According to @see \Magento\LayeredNavigationStaging\Block\Navigation::canShowBlock
     * We should not show the block if staging is enabled and if we are currently previewing the results.
     *
     * @return bool
     */
    public function canShowBlock()
    {
        $canShowBlock = parent::canShowBlock();

        if ($this->moduleManager->isEnabled('Magento_Staging')) {
            try {
                $versionManager = $this->objectManager->get('\Magento\Staging\Model\VersionManager');

                $canShowBlock = $canShowBlock && !$versionManager->isPreviewVersion();
            } catch (\Exception $exception) {
                ;
            }
        }

        if ($this->getLayer() instanceof \Magento\Catalog\Model\Layer\Category &&
            $this->getLayer()->getCurrentCategory()->getDisplayMode() === \Magento\Catalog\Model\Category::DM_PAGE) {
            $canShowBlock = false;
        }

        return $canShowBlock;
    }

    /**
     * Return index of the facets that are expanded for the current page :
     *
     *  - nth first facets (depending of config)
     *  - facets with at least one selected filter
     *
     * @return string
     */
    public function getActiveFilters()
    {
        $activeFilters = [];

        if (!$this->isInline()) {
            $requestParams    = array_keys($this->getRequest()->getParams());
            $displayedFilters = $this->getDisplayedFilters();
            $expandedFacets = (int) $this->_scopeConfig->getValue(
                self::DEFAULT_EXPANDED_FACETS_COUNT_CONFIG_XML_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $activeFilters    = [];
            if ($expandedFacets > 0) {
                $activeFilters = range(0, min(count($displayedFilters), $expandedFacets) - 1);
            }

            foreach ($displayedFilters as $index => $filter) {
                if (in_array($filter->getRequestVar(), $requestParams)) {
                    $activeFilters[] = $index;
                }
            }
        }

        return json_encode($activeFilters);
    }

    /**
     * Returns facet that are displayed.
     *
     * @return array
     */
    public function getDisplayedFilters()
    {
        $displayedFilters = array_filter(
            $this->getFilters(),
            function ($filter) {
                return $filter->getItemsCount() > 0;
            }
        );

        return array_values($displayedFilters);
    }

    /**
     * Indicates if the block is displayed inline or not.
     *
     * @return boolean
     */
    public function isInline()
    {
        return in_array($this->pageLayout, $this->inlineLayouts);
    }
}
