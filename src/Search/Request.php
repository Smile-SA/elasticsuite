<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search;

use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;
use Magento\Framework\Search\Request\QueryInterface;

class Request extends \Magento\Framework\Search\Request implements RequestInterface
{
    private $type;
    private $sortOrder;
    private $filter;

    /**
     * @param string $name
     * @param string $indexName
     * @param QueryInterface $query
     * @param int|null $from
     * @param int|null $size
     * @param Dimension[] $dimensions
     * @param RequestBucketInterface[] $buckets
     */
    public function __construct(
        $name,
        $indexName,
        $type,
        QueryInterface $query,
        QueryInterface $filter,
        SortOrderInterface $sortOrder = null,
        $from = null,
        $size = null,
        array $dimensions = [],
        array $buckets = []
    ) {
        parent::__construct($name, $indexName, $query, $from, $size, $dimensions, $buckets);
        $this->type = $type;
        $this->filter = $filter;
        $this->sortOrder = $sortOrder;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }
}