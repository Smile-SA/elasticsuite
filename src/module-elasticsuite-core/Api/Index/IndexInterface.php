<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Representation of a Elasticsearch index.
 *
 * @category Smile_Elasticsuite
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
     * List of the types handled by the index.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\TypeInterface[]
     */
    public function getTypes();

    /**
     * Retrieve an type by it's name.
     *
     * @param string $typeName Name of the retrieved type.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\TypeInterface
     */
    public function getType($typeName);

    /**
     * Each index has a default type that can be used to search.
     * This method returns this default type.
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
}
