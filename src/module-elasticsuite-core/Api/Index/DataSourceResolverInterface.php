<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Api\Index;

/**
 * Datasources Resolver interface.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 */
interface DataSourceResolverInterface
{
    /**
     * Get Data sources of a given index/type combination.
     *
     * @param string $indexName The index name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDataSources(string $indexName);
}
