<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\Modifier;

use Smile\ElasticsuiteCatalog\Search\Request\Product\Aggregation\Provider\FilterableAttributes\ModifierInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerConfigurationInterface;

/**
 * AjaxFilter modifier for filterable attributes aggregation provider.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AjaxFilter implements ModifierInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

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
     * @param \Magento\Framework\App\RequestInterface                  $request             HTTP Request
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductAttribute       $mappingHelper       Mapping Helper
     * @param \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository Attribute Repository
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Smile\ElasticsuiteCatalog\Helper\ProductAttribute $mappingHelper,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->request             = $request;
        $this->mappingHelper       = $mappingHelper;
        $this->attributeRepository = $attributeRepository;
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
            if ($aggregationName) {
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
        $attributeCode = false;

        if ($this->request->isAjax() && $this->request->getParam('filterName')) {
            $attributeCode = (string) $this->request->getParam('filterName');
        }

        return $attributeCode;
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
