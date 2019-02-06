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
namespace Smile\ElasticsuiteCatalog\Search\Request\Product\Coverage;

/**
 * Catalog Product Search Request coverage provider
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Provider
{
    /**
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * @var array
     */
    private $countByAttributeSet;

    /**
     * @var array
     */
    private $countByAttributeCode;

    /**
     * @var integer
     */
    private $size;

    /**
     * Provider constructor.
     *
     * @param \Magento\Search\Model\SearchEngine              $searchEngine Search Engine
     * @param \Smile\ElasticsuiteCore\Search\RequestInterface $request      Search Request
     */
    public function __construct(
        \Magento\Search\Model\SearchEngine $searchEngine,
        \Smile\ElasticsuiteCore\Search\RequestInterface $request
    ) {
        $this->searchEngine = $searchEngine;
        $this->request      = $request;
    }

    /**
     * Load the product count by attribute set id.
     *
     * @return array
     */
    public function getProductCountByAttributeSetId()
    {
        if ($this->countByAttributeSet === null) {
            $this->loadProductCounts();
        }

        return $this->countByAttributeSet;
    }

    /**
     * Load the product count by attribute code.
     *
     * @return array
     */
    public function getProductCountByAttributeCode()
    {
        if ($this->countByAttributeCode === null) {
            $this->loadProductCounts();
        }

        return $this->countByAttributeCode;
    }

    /**
     * Get total count
     *
     * @return int
     */
    public function getSize()
    {
        if ($this->size === null) {
            $this->loadProductCounts();
        }

        return $this->size;
    }

    /**
     * Compute calculation of product counts against the engine.
     */
    private function loadProductCounts()
    {
        $searchResponse = $this->searchEngine->search($this->request);

        $this->countByAttributeSet  = [];
        $this->countByAttributeCode = [];
        $this->size                 = $searchResponse->count();

        $attributeSetIdBucket = $searchResponse->getAggregations()->getBucket('attribute_set_id');
        $attributeCodeBucket  = $searchResponse->getAggregations()->getBucket('indexed_attributes');

        if ($attributeSetIdBucket) {
            foreach ($attributeSetIdBucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $this->countByAttributeSet[$value->getValue()] = $metrics['count'];
            }
        }

        if ($attributeCodeBucket) {
            foreach ($attributeCodeBucket->getValues() as $value) {
                $metrics = $value->getMetrics();
                $this->countByAttributeCode[$value->getValue()] = $metrics['count'];
            }
        }
    }
}
