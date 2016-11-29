<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer\Filter\Item;

/**
 * Attribute item filter override.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Attribute extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        $qsParams = $this->getApplyQueryStringParams();

        $url = $this->rewriteBaseUrl($qsParams);

        if ($url === null) {
            $url = $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $qsParams]);
        }

        return $url;
    }

    /**
     * Append url and is_selected computed fields to the result array.
     *
     * {@inheritDoc}
     */
    public function toArray(array $keys = [])
    {
        $data = parent::toArray($keys);

        if (in_array('url', $keys) || empty($keys)) {
            $data['url'] = $this->getUrl();
        }

        if (in_array('is_selected', $keys) || empty($keys)) {
            $data['is_selected'] = (bool) $this->getIsSelected();
        }

        return $data;
    }

    /**
     * Return the value used to apply the filter.
     *
     * @return string|array
     */
    private function getApplyValue()
    {
        $value = $this->getValue();

        if (is_array($this->getApplyFilterValue())) {
            $value = $this->getApplyFilterValue();
        }

        if (is_array($value) && count($value) == 1) {
            $value = current($value);
        }

        return $value;
    }

    /**
     * Get query string params used to apply the filter.
     * @return array
     */
    private function getApplyQueryStringParams()
    {
        $qsParams = [
            $this->getFilter()->getRequestVar()      => $this->getApplyValue(),
            $this->_htmlPagerBlock->getPageVarName() => null,
        ];

        return $qsParams;
    }

    /**
     * Create the URL used to apply the filter from a existing URL.
     *
     * @param array $qsParams Query string params.
     *
     * @return NULL|string
     */
    private function rewriteBaseUrl($qsParams)
    {
        $url = null;

        if ($this->getBaseUrl()) {
            $baseUrlParts = explode('?', $this->getBaseUrl());
            $qsParser     = new \Zend\Stdlib\Parameters();

            $qsParser->fromArray($qsParams);

            if (count($baseUrlParts) > 1) {
                $qsParser->fromString($baseUrlParts[1]);
                $qsParams = array_merge($qsParser->toArray(), $qsParams);
                $qsParser->fromArray($qsParams);
            }

            $baseUrlParts[1] = $qsParser->toString();

            $url = implode('?', $baseUrlParts);
        }

        return $url;
    }
}
