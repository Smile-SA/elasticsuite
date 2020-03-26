<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier;

use Smile\ElasticsuiteCatalog\Model\Attribute\Source\FilterDisplayMode;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory as CoverageProviderFactory;

/**
 * Coverage Modifier for filterable attributes provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Coverage implements ModifierInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\Builder
     */
    private $coverageRequestBuilder;

    /**
     * @var \Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory
     */
    private $coverageProviderFactory;

    /**
     * Coverage constructor.
     *
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder                             $coverageRequestBuilder  Coverage Request builder.
     * @param \Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage\ProviderFactory $coverageProviderFactory Coverage provider factory.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Search\Request\Builder $coverageRequestBuilder,
        CoverageProviderFactory $coverageProviderFactory
    ) {
        $this->coverageRequestBuilder  = $coverageRequestBuilder;
        $this->coverageProviderFactory = $coverageProviderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAttributes($storeId, $requestName, $attributes, $query, $filters, $queryFilters)
    {
        $relevantAttributes = [];
        $coverageRates      = $this->getCoverageRates($storeId, $requestName, $query, $filters, $queryFilters);

        foreach ($attributes as $attribute) {
            if ('category_ids' === $attribute->getAttributeCode()) {
                array_unshift($relevantAttributes, $attribute);
                continue;
            }

            try {
                $attributeCode   = $attribute->getAttributeCode();
                $minCoverageRate = $attribute->getFacetMinCoverageRate();

                $isRelevant   = isset($coverageRates[$attributeCode]) && ($coverageRates[$attributeCode] >= $minCoverageRate);
                $forceDisplay = $attribute->getFacetDisplayMode() == FilterDisplayMode::ALWAYS_DISPLAYED;
                $isHidden     = $attribute->getFacetDisplayMode() == FilterDisplayMode::ALWAYS_HIDDEN;

                if (!($isHidden || !($isRelevant || $forceDisplay))) {
                    $relevantAttributes[] = $attribute;
                }
            } catch (\Exception $e) {
                ;
            }
        }

        return $relevantAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAggregations($storeId, $requestName, $aggregations, $query, $filters, $queryFilters)
    {
        return $aggregations;
    }

    /**
     * Get coverage rate of attributes for current search request.
     *
     * @param int    $storeId      The Store ID.
     * @param string $requestName  The Request name.
     * @param null   $query        Current Query
     * @param array  $filters      Applied filters
     * @param array  $queryFilters Applied Query Filters
     *
     * @return array
     */
    private function getCoverageRates($storeId, $requestName, $query = null, $filters = [], $queryFilters = [])
    {
        $coverageRequest = $this->coverageRequestBuilder->create(
            $storeId,
            $requestName,
            0,
            0,
            $query,
            [],
            $filters,
            $queryFilters
        );

        $coverage      = $this->coverageProviderFactory->create(['request' => $coverageRequest]);
        $coverageRates = [];
        $totalCount    = $coverage->getSize();

        foreach ($coverage->getProductCountByAttributeCode() as $attributeCode => $productCount) {
            $coverageRates[$attributeCode] = $productCount / $totalCount * 100;
        }

        return $coverageRates;
    }
}
