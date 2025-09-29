<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Representation of a Elasticsearch index.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface IndexInterface
{
    /**
     * Index identifier (eg: catalog_product).
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Index name.
     *
     * Can be :
     *  - a real index name (eg.: magento2_catalog_product_20160202_192935
     *  - an alias (eg. : magento2_catalog_product).
     *
     * @return string
     */
    public function getName();

    /**
     * Each index has a default type that can be used to search.
     * This method returns this default type.
     *
     * @deprecated
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\TypeInterface
     */
    public function getDefaultSearchType();

    /**
     * Indicates if the index needs to be installed.
     *
     * @return boolean
     */
    public function needInstall();

    /**
     * Mapping describing all the field of the current type.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\MappingInterface
     */
    public function getMapping();

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();

    /**
     * Check if index contains knn fields.
     *
     * @return bool
     */
    public function useKnn() : bool;
}
