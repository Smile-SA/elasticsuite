<?php
/**
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Vladimir Bratukhin <insyon@gmail.com>
 * @copyright 2017 Smile
 */

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
     * @param int   $storeId Store ID
     * @param array $data    Data
     *
     * @return $this
     */
    public function addProductData($storeId, array $data);

    /**
     * Add child product data to extension object
     *
     * @param int   $storeId Store ID
     * @param array $data    Data
     * @param int   $childId Child identifier
     * @return $this
     */
    public function addChildData($storeId, array $data, $childId);

    /**
     * Add inventory data to extension object
     *
     * @param int   $storeId Store ID
     * @param array $data    Data
     *
     * @return $this
     */
    public function addInventoryData($storeId, array $data);

    /**
     * Add category data to extension object
     *
     * @param int   $storeId Store ID
     * @param array $data    Data
     *
     * @return $this
     */
    public function addCategoryData($storeId, array $data);

    /**
     * Add price data to extension object
     *
     * @param int   $storeId Store ID
     * @param array $data    Data
     *
     * @return $this
     */
    public function addPriceData($storeId, array $data);
}
