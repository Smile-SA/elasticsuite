<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\DefaultFilter;

/**
 * Search Request Containers Default Filters pool.
 * Will contain default filtering for search containers (visibility, stock, etc ...)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Pool
{
    /**
     * @var array
     */
    private $filters;

    /**
     * Pool constructor.
     *
     * @param array $filters The filters injected via DI.
     */
    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param string $containerName The container name
     *
     * @return \Smile\ElasticsuiteCore\Api\Search\Request\Container\DefaultFilterInterface[]
     */
    public function getFilters($containerName)
    {
        return $this->filters[$containerName] ?? [];
    }
}
