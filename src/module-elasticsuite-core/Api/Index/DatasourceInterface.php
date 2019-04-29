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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Datasources interface.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface DatasourceInterface
{
    /**
     * Append data to a list of documents.
     *
     * @param integer $storeId   Store id.
     * @param array   $indexData List of documents to get enriched by the datasources.
     *
     * @return array
     */
    public function addData($storeId, array $indexData);
}
