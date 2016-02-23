<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\Request\Binder;
use Smile\ElasticSuiteCore\Search\Request\Config;
use Smile\ElasticSuiteCore\Search\Request\Builder\Cleaner;
use Magento\Framework\Search\SearchEngineInterface;
use Smile\ElasticSuiteCore\Search\Adapter\ElasticSuite\Query\Builder\Bool;
use Magento\Framework\Search\Response\Bucket;
use Magento\Framework\Search\Request\BucketInterface;
use Smile\ElasticSuiteCore\Search\RequestInterface;

/**
 * ElasticSuite search requests builder.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Builder
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Binder
     */
    private $binder;

    /**
     * @var array
     */
    private $data = [
        'dimensions'  => [],
        'placeholder' => [],
        'sortOrders'  => [],
    ];

    /**
     * @var Cleaner
     */
    private $cleaner;

    /**
     * Request Builder constructor
     *
     * @param ObjectManagerInterface $objectManager Object manager.
     * @param Config                 $config        Search requests configuration.
     * @param Binder                 $binder        Binder.
     * @param Cleaner                $cleaner       Cleaner.
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config,
        Binder $binder,
        Cleaner $cleaner
    ) {
        $this->objectManager = $objectManager;
        $this->config        = $config;
        $this->binder        = $binder;
        $this->cleaner       = $cleaner;
    }


    /**
     * Set request name
     *
     * @param string $requestName Request name.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setRequestName($requestName)
    {
        $this->data['requestName'] = $requestName;

        return $this;
    }

    /**
     * Set page size for the request.
     *
     * @param int $size Page size.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setSize($size)
    {
        $this->data['size'] = $size;

        return $this;
    }

    /**
     * Set the search pagination offset.
     *
     * @param int $from Pagination offset
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function setFrom($from)
    {
        $this->data['from'] = $from;

        return $this;
    }

    /**
     * Bind dimension data by name
     *
     * @param string $name  Dimension name.
     * @param string $value Dimension value.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function bindDimension($name, $value)
    {
        $this->data['dimensions'][$name] = $value;

        return $this;
    }

    /**
     * Add a new sort order to the request.
     *
     * @param string $name      Sort order name (reference to a sort order declared into the configuration).
     * @param string $direction Sort order direction.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function bindSortOrder($name, $direction)
    {
        $this->data['sortOrders'][$name] = strtolower($direction);

        return $this;
    }

    /**
     * Bind data to placeholder
     *
     * @param string $placeholder Placeholder name.
     * @param mixed  $value       Binded value.
     *
     * @return \Smile\ElasticSuiteCore\Search\Request\Builder
     */
    public function bind($placeholder, $value)
    {
        $this->data['placeholder']['$' . $placeholder . '$'] = $value;

        return $this;
    }

    /**
     * Create request object
     *
     * @return RequestInterface
     */
    public function create()
    {
        if (!isset($this->data['requestName'])) {
            throw new \InvalidArgumentException("Request name not defined.");
        }
        $requestName = $this->data['requestName'];

        $data = $this->getConfig($requestName);

        $data = $this->prepareSortOrders($data);

        // Binder hopes to find a filters field into the array.
        // We put this even if it does not make sense for our builder.
        $data['filters'] = [];
        $data = $this->binder->bind($data, $this->data);
        $data = $this->cleaner->clean($data);
        $data = $this->convert($data);

        return $data;
    }

    /**
     * Load configuration for a request by name and returns it as an array.
     *
     * @throws \InvalidArgumentException
     *
     * @param string $requestName Request name.
     *
     * @return array
     */
    protected function getConfig($requestName)
    {
        $data = $this->config->get($requestName);

        if ($data === null) {
            throw new \InvalidArgumentException("Request name '{$requestName}' doesn't exist.");
        }

        return $data;
    }

    /**
     * Convert array to RequestInterface instance.
     *
     * @param array $data Converted data.
     *
     * @return RequestInterface
     */
    private function convert($data)
    {
        $mapperClass = 'Smile\ElasticSuiteCore\Search\Request\Builder\Mapper';
        /** @var Mapper $mapper */
        $mapper = $this->objectManager->create($mapperClass, ['requestData' => $data]);

        $searchRequestParams = [
            'name'       => $data['name'],
            'indexName'  => $data['index'],
            'type'       => $data['type'],
            'from'       => $data['from'],
            'size'       => $data['size'],
            'query'      => $mapper->getRootQuery(),
            'filter'     => $mapper->getRootFilter(),
            'buckets'    => $mapper->getAggregations(),
            'sortOrders' => $mapper->getSortOrders(),
            'dimensions' => $this->buildDimensions(isset($data['dimensions']) ? $data['dimensions'] : []),
        ];

        return $this->objectManager->create('Smile\ElasticSuiteCore\Search\Request', $searchRequestParams);
    }

    /**
     * Bind dimension data to the built query.
     *
     * @param array $dimensionsData Binded data.
     *
     * @return array
     */
    private function buildDimensions(array $dimensionsData)
    {
        $dimensions = [];

        foreach ($dimensionsData as $dimensionData) {
            $dimensions[$dimensionData['name']] = $this->objectManager->create(
                'Magento\Framework\Search\Request\Dimension',
                $dimensionData
            );
        }

        return $dimensions;
    }

    /**
     * Prepare the search request config data to applied binded sort orders.
     *
     * @param array $data Request config data.
     *
     * @return array
     */
    private function prepareSortOrders($data)
    {
        $sortOrders           = [];
        $hasDefaultSortOrder  = false;
        $defaultSortOrderName = SortOrderInterface::DEFAULT_SORT_NAME;

        foreach ($this->data['sortOrders'] as $sortOrderName => $direction) {
            if (isset($data['sortOrders']) && isset($data['sortOrders'][$sortOrderName])) {
                $sortOrder = $data['sortOrders'][$sortOrderName];

                $sortOrder['direction'] = $direction;

                $sortOrders[] = $sortOrder;

                if ($sortOrderName == $defaultSortOrderName) {
                    $hasDefaultSortOrder = true;
                }
            }
        }

        if (!$hasDefaultSortOrder && isset($data['sortOrders']) && isset($data['sortOrders'][$defaultSortOrderName])) {
            $sortOrders[] = [
                'type'      => SortOrderInterface::TYPE_STANDARD,
                'name'      => $defaultSortOrderName,
                'field'     => SortOrderInterface::DEFAULT_SORT_FIELD,
                'direction' => SortOrderInterface::DEFAULT_SORT_DIRECTION,
            ];
        }

        $data['sortOrders'] = $sortOrders;

        return $data;
    }
}
