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

namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter;

/**
 * Decimal filter model methods.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
trait DecimalFilterTrait
{
    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request The request
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());

        if ($filter && !is_array($filter)) {
            $filterParams = explode(',', $filter);
            $filter = $this->dataProvider->validateFilter($filterParams[0]);
            if ($filter) {
                $this->dataProvider->setInterval($filter);
                $priorFilters = $this->dataProvider->getPriorFilters($filterParams);
                if ($priorFilters) {
                    $this->dataProvider->setPriorIntervals($priorFilters);
                }

                list($fromValue, $toValue) = $filter;
                $this->setCurrentValue(['from' => $fromValue, 'to' => $toValue]);

                $bounds = array_filter(['gte' => $fromValue, 'lt' => $toValue]);
                if ($fromValue === $toValue) {
                    $bounds = array_filter(['gte' => $fromValue, 'lte' => $toValue]);
                }

                $this->getLayer()->getProductCollection()->addFieldToFilter(
                    $this->getFilterField(),
                    $this->getRangeCondition($bounds)
                );

                $this->getLayer()->getState()->addFilter(
                    $this->_createItem($this->_renderRangeLabel(empty($fromValue) ? 0 : $fromValue, $toValue), $filter)
                );
            }
        }

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * Get data array for building attribute filter items
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = $productCollection->getFacetedData($this->getFilterField());
        $products = $productCollection->addAttributeToSelect($this->_requestVar)->getItems();

        $minValue = false;
        $maxValue = false;

        $data = [];
        if (!count($facets)) {
            return $data;
        }

        foreach($products as $product) {
            $value = $product->getData($this->_requestVar);
            if ($minValue === false || $minValue > $value) {
                $minValue = $value;
            }

            if ($maxValue === false || $maxValue < $value) {
                $maxValue = $value;
            }
        }

        foreach ($facets as $key => $aggregation) {
            $count = $aggregation['count'];
            if ($key >= $minValue && $key <= $maxValue) {
                $data[] = ['label' => $key, 'value' => $key, 'count' => $count];
            }
        }

        $this->setMinValue($minValue);
        $this->setMaxValue($maxValue);

        return $data;
    }
}
