<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogGraphQl\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCatalogGraphQl\Model\Layer\Filter\ViewMore\Context as ViewMoreContext;

/**
 * ViewMore modifier for filterable attributes aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ViewMore implements ModifierInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogGraphQl\Model\Layer\Filter\ViewMore\Context
     */
    private $viewMoreContext;

    /**
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductAttribute
     */
    private $mappingHelper;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * AjaxFilter constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute       $mappingHelper       Mapping Helper
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository Attribute Repository
     * @param ViewMoreContext                                          $viewMoreContext     View More Context
     */
    public function __construct(
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository,
        ViewMoreContext $viewMoreContext
    ) {
        $this->mappingHelper       = $mappingHelper;
        $this->attributeRepository = $attributeRepository;
        $this->viewMoreContext     = $viewMoreContext;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAttributes($storeId, $requestName, $attributes, $query, $filters, $queryFilters)
    {
        $relevantAttributes = $attributes;
        if ($this->getAttributeCode()) {
            $attributeCode = $this->getAttributeCode();
            $relevantAttributes = array_filter($attributes, function ($attribute) use ($attributeCode) {
                return ($attribute->getAttributeCode() === $attributeCode);
            });
        }

        return $relevantAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyAggregations($storeId, $requestName, $aggregations, $query, $filters, $queryFilters)
    {
        if ($this->getAttributeCode()) {
            $aggregationName = $this->getFilterField();
            if ($aggregationName && isset($aggregations[$aggregationName])) {
                $aggregations[$aggregationName]['size'] = 0;
            }
        }

        return $aggregations;
    }

    /**
     * Get attribute code
     *
     * @return bool|string
     */
    private function getAttributeCode()
    {
        return $this->viewMoreContext->getFilterName() ?? false;
    }

    /**
     * Get attribute
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function getAttribute()
    {
        return $this->attributeRepository->get($this->getAttributeCode());
    }

    /**
     * Get filter field
     *
     * @return bool|string
     */
    private function getFilterField()
    {
        try {
            $attribute = $this->getAttribute();
            $field     = $this->mappingHelper->getFilterField($attribute);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $field = $this->getAttributeCode();
        }

        return $field;
    }
}
