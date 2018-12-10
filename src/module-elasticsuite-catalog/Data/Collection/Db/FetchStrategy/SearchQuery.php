<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Data\Collection\Db\FetchStrategy;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\DB\Select;
use Magento\Search\Model\SearchEngine;
use Smile\ElasticsuiteCatalog\Helper\ProductListing;
use Magento\Framework\Data\Collection\Db\FetchStrategy\Query;
use Smile\ElasticsuiteCore\Helper\Mapping;

/**
 * Class SearchQuery
 */
class SearchQuery extends Query implements FetchStrategyInterface
{
    /**
     * Search Request
     *
     * @var \Smile\ElasticsuiteCore\Search\RequestInterface
     */
    private $request;

    /**
     *
     *
     * @var \Magento\Framework\Search\ResponseInterface
     */
    private $response;

    /**
     * Search Engine
     *
     * @var \Magento\Search\Model\SearchEngine
     */
    private $searchEngine;

    /**
     * Product Listing helper used to check if product listing is enabled
     *
     * @var \Smile\ElasticsuiteCatalog\Helper\ProductListing
     */
    private $productListingHelper;

    /**
     * SearchQuery constructor.
     *
     * @param \Magento\Search\Model\SearchEngine               $searchEngine         Search Engine
     * @param \Smile\ElasticsuiteCatalog\Helper\ProductListing $productListingHelper Product Listing Helper
     */
    public function __construct(
        SearchEngine $searchEngine,
        ProductListing $productListingHelper
    ) {
        $this->searchEngine = $searchEngine;
        $this->productListingHelper = $productListingHelper;
    }

    /**
     * @param \Smile\ElasticsuiteCore\Search\RequestInterface $request Search Request
     */
    public function setRequest(\Smile\ElasticsuiteCore\Search\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return \Magento\Framework\Search\ResponseInterface|null Search Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \Magento\Framework\DB\Select $select     Database select statement
     * @param string[]                     $bindParams Database select statement bind params
     * @return array[][]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetchAll(Select $select, array $bindParams = [])
    {
        if (!$this->request) {
            return parent::fetchAll($select, $bindParams);
        }

        $this->response = $this->searchEngine->search($this->request);
        $products = [];

        foreach ($this->response->getIterator() as $item) {
            /** @var \Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\Document $item */
            $source = $item->getSource();
            $source = array_merge($source, $source['price'][0]);

            foreach ($source as $attributeCode => $value) {
                $source[$attributeCode] = in_array($attributeCode, $source['indexed_attributes'])
                && is_array($value) ? count($value) > 1 ? $value : $value[0] : $value;

                if (0 === strpos($attributeCode, Mapping::OPTION_TEXT_PREFIX)) {
                    $key = str_replace(
                        Mapping::OPTION_TEXT_PREFIX.'_',
                        '',
                        $attributeCode
                    );
                    $source[$key.'_value'] = is_array($value) ? count($value) > 1 ? $value : $value[0] : $value;
                }
            }

            $products[] = $source;
        }

        return $products;
    }
}
