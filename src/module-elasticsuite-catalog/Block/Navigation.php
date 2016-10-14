<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block;

use Magento\Catalog\Model\Layer\AvailabilityFlagInterface;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\Template\Context;

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
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Manager
     */
    private $moduleManager;

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
        Context $context,
        Resolver $layerResolver,
        FilterList $filterList,
        AvailabilityFlagInterface $visibilityFlag,
        ObjectManagerInterface $objectManager,
        Manager $moduleManager,
        array $data
    ) {
        $this->objectManager = $objectManager;
        $this->moduleManager = $moduleManager;

        parent::__construct($context, $layerResolver, $filterList, $visibilityFlag, $data);
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
        if ($this->moduleManager->isEnabled('Magento_Staging')) {
            try {
                $versionManager = $this->objectManager->get('\Magento\Staging\Model\VersionManager');

                return parent::canShowBlock() && !$versionManager->isPreviewVersion();
            } catch (\Exception $exception) {
                return parent::canShowBlock();
            }
        }

        return parent::canShowBlock();
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
        $requestParams    = array_keys($this->getRequest()->getParams());
        $displayedFilters = $this->getDisplayedFilters();
        $expandedFacets   = $this->_scopeConfig->getValue(self::DEFAULT_EXPANDED_FACETS_COUNT_CONFIG_XML_PATH);
        $activeFilters    = range(0, min(count($displayedFilters), $expandedFacets) - 1);

        foreach ($displayedFilters as $index => $filter) {
            if (in_array($filter->getRequestVar(), $requestParams)) {
                $activeFilters[] = $index;
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
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addFacets();

        return $this;
    }

    /**
     * Append facets to the search requests using the coverage rate defined in admin.
     *
     * @return void
     */
    private function addFacets()
    {
        $productCollection = $this->getLayer()->getProductCollection();
        $countBySetId      = $productCollection->getProductCountByAttributeSetId();
        $totalCount        = $productCollection->getSize();

        foreach ($this->filterList->getFilters($this->_catalogLayer) as $filter) {
            try {
                $attribute                = $filter->getAttributeModel();
                $facetCoverageRate        = $attribute->getFacetMinCoverageRate();
                $attributeCountCandidates = array_sum(array_intersect_key($countBySetId, $attribute->getAttributeSetInfo()));
                $currentCoverageRate      = $attributeCountCandidates / $totalCount * 100;

                if ($facetCoverageRate < $currentCoverageRate) {
                    $filter->addFacetToCollection();
                }
            } catch (\Exception $e) {
                $filter->addFacetToCollection();
            }
        }
    }
}
