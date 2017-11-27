<?php

namespace Smile\ElasticsuiteCatalog\Api;
use Smile\ElasticsuiteCore\Api\Index\DocumentExtensionInterface;

/**
 * Product DataExtension service contract for adding extra information to ES
 *
 * Interface ProductDataExtensionInterface
 * @package Smile\ElasticsuiteCatalog\Api
 *
 * @author Vladimir Bratukhin <insyon@gmail.com>
 */
interface ProductDataExtensionInterface extends DocumentExtensionInterface
{
    /**
     * Add product data to extension object
     *
     * @param array $data
     *
     * @return $this
     */
    public function addProductData(array $data);

    /**
     * Add child product data to extension object
     *
     * @param array $data
     * @param $childId
     *
     * @return $this
     */
    public function addChildData(array $data, $childId);

    /**
     * Add inventory data to extension object
     *
     * @param array $data
     *
     * @return $this
     */
    public function addInventoryData(array $data);

    /**
     * Add category data to extension object
     *
     * @param array $data
     *
     * @return $this
     */
    public function addCategoryData(array $data);

    /**
     * Add price data to extension object
     *
     * @param array $data
     *
     * @return $this
     */
    public function addPriceData(array $data);
}
