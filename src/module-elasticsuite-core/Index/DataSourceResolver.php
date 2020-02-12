<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Index;

use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCore\Api\Index\DataSourceResolverInterface;

/**
 * Datasource Resolver for index types.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class DataSourceResolver implements DataSourceResolverInterface
{
    /**
     * @var array
     */
    private $datasources;

    /**
     * DataSourceResolver constructor.
     *
     * @param array $datasources The datasources (from DI).
     */
    public function __construct(array $datasources = [])
    {
        $this->datasources = $datasources;
    }

    /**
     * Get Data sources of a given index.
     *
     * @param string $indexName The index name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDataSources(string $indexName)
    {
        $sources = [];

        if (isset($this->datasources[$indexName]) && isset($this->datasources[$indexName])) {
            foreach ($this->datasources[$indexName] as $name => $datasource) {
                if (!$datasource instanceof \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface) {
                    throw new \InvalidArgumentException(
                        'Datasource must implement ' . \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface::class
                    );
                }
                $sources[$name] = $datasource;
            }
        }

        return $sources;
    }
}
