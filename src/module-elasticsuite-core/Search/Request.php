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

namespace Smile\ElasticsuiteCore\Search;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;
use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\Dimension;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;

/**
 * Default implementation of ElasticSuite search request.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
     * @var integer
     */
    private $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string               $name         Search request name.
     * @param string               $indexName    Index name.
     * @param string               $type         Searched document type.
     * @param QueryInterface       $query        Search query.
     * @param QueryInterface       $filter       Search filter.
     * @param SortOrderInterface[] $sortOrders   Sort orders specification.
     * @param int|null             $from         Pagination from clause.
     * @param int|null             $size         Pagination page size clause.
     * @param Dimension[]          $dimensions   Searched store.
     * @param BucketInterface[]    $buckets      Search request aggregations definition.
     * @param string               $spellingType For fulltext query : the type of spellchecked applied.
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
        array $buckets = [],
        $spellingType = null
    ) {
        parent::__construct($name, $indexName, $query, $from, $size, $dimensions, $buckets);
        $this->type = $type;
        $this->filter = $filter;
        $this->sortOrders = $sortOrders;

        if ($spellingType !== null) {
            $this->spellingType = $spellingType;
        }
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

    /**
     * {@inheritDoc}
     */
    public function isSpellchecked()
    {
        $fuzzySpellingTypes = [
            SpellcheckerInterface::SPELLING_TYPE_FUZZY,
            SpellcheckerInterface::SPELLING_TYPE_MOST_FUZZY,
        ];

        return in_array($this->spellingType, $fuzzySpellingTypes);
    }
}
