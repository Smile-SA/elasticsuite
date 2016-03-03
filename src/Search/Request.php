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

namespace Smile\ElasticSuiteCore\Search;

use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;
use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticSuiteCore\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Dimension;

/**
 * Default implementation of ElasticSuite search request.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Request extends \Magento\Framework\Search\Request implements RequestInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var SortOrderInterface
     */
    private $sortOrders;

    /**
     * @var QueryInterface
     */
    private $filter;

    /**
     * @param string               $name       Search request name.
     * @param string               $indexName  Index name.
     * @param string               $type       Searched document type.
     * @param QueryInterface       $query      Search query.
     * @param QueryInterface       $filter     Search filter.
     * @param SortOrderInterface[] $sortOrders Sort orders specification.
     * @param int|null             $from       Pagination from clause.
     * @param int|null             $size       Pagination page size clause.
     * @param Dimension[]          $dimensions Searched store.
     * @param BucketInterface[]    $buckets    Search request aggregations definition.
     */
    public function __construct(
        $name,
        $indexName,
        $type,
        QueryInterface $query,
        QueryInterface $filter = null,
        array $sortOrders = null,
        $from = null,
        $size = null,
        array $dimensions = [],
        array $buckets = []
    ) {
        parent::__construct($name, $indexName, $query, $from, $size, $dimensions, $buckets);
        $this->type = $type;
        $this->filter = $filter;
        $this->sortOrders = $sortOrders;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrders()
    {
        return $this->sortOrders;
    }
}
