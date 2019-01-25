<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Index\Type;

use Smile\ElasticsuiteCore\Api\Index\Type\DataSourceResolverInterface;

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
     * @param array $datasources The datasources.
     */
    public function __construct(array $datasources = [])
    {
        $this->datasources = $datasources;
    }

    /**
     * Get Data sources of a given index/type combination.
     *
     * @param string $indexName The index name.
     * @param string $typeName  The type name.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface[]
     */
    public function getDataSources(string $indexName, string $typeName)
    {
        $sources = [];

        if (isset($this->datasources[$indexName]) && isset($this->datasources[$indexName][$typeName])) {
            foreach ($this->datasources[$indexName][$typeName] as $name => $datasource) {
                if (! $datasource instanceof \Smile\ElasticsuiteCore\Api\Index\DatasourceInterface) {
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
