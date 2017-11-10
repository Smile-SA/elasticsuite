<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model;

use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;

/**
 * Coverage Provider for Fulltext collection.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CoverageProvider
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection
     */
    private $productCollection;

    /**
     * CoverageProvider constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection Product Collection
     */
    public function __construct(\Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Fulltext\Collection $collection)
    {
        $this->productCollection = $collection;
    }

    /**
     * Get coverage by attributes for a product collection
     *
     * @return array
     */
    public function getAttributesCoverage()
    {
        $this->productCollection->addFacet('indexed_attributes', BucketInterface::TYPE_TERM, ['size' => 0]);
        $this->productCollection->setPageSize(0);

        $bucket = $this->productCollection->getFacetedData('indexed_attributes');

        if (isset($bucket['__other_docs'])) {
            unset($bucket['__other_docs']);
        }

        return $bucket;
    }
}
