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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search;

use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Api\Search\SpellcheckerInterface;
use Smile\ElasticsuiteCore\Search\Request\BucketInterface;
use Smile\ElasticsuiteCore\Search\Request\CollapseInterface;
use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

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
     * @var SortOrderInterface
     */
    private $sortOrders;

    /**
     * @var QueryInterface
     */
    private $filter;

    /**
     * @var CollapseInterface
     */
    private $collapse;

    /**
     * @var integer
     */
    private $spellingType = SpellcheckerInterface::SPELLING_TYPE_EXACT;

    /**
     * @var boolean|integer
     */
    private $trackTotalHits = \Smile\ElasticsuiteCore\Helper\IndexSettings::PER_SHARD_MAX_RESULT_WINDOW;

    /**
     * @var boolean|integer
     */
    private $minScore;

    /**
     * @var array
     */
    private $sourceConfig = [];

    /**
     * Constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     *
     * @param string               $name           Search request name.
     * @param string               $indexName      Index name.
     * @param QueryInterface       $query          Search query.
     * @param QueryInterface       $filter         Search filter.
     * @param SortOrderInterface[] $sortOrders     Sort orders specification.
     * @param int|null             $from           Pagination from clause.
     * @param int|null             $size           Pagination page size clause.
     * @param Dimension[]          $dimensions     Searched store.
     * @param BucketInterface[]    $buckets        Search request aggregations definition.
     * @param string               $spellingType   For fulltext query : the type of spellchecked applied.
     * @param bool|int             $trackTotalHits Value of the 'track_total_hits' ES parameter.
     * @param bool|int             $minScore       Value of the 'min_score' ES parameter.
     */
    public function __construct(
        $name,
        $indexName,
        QueryInterface $query,
        ?QueryInterface $filter = null,
        ?array $sortOrders = null,
        $from = null,
        $size = null,
        array $dimensions = [],
        array $buckets = [],
        $spellingType = null,
        $trackTotalHits = null,
        $minScore = null
    ) {
        parent::__construct($name, $indexName, $query, $from, $size, $dimensions, $buckets);
        $this->filter = $filter;
        $this->sortOrders = $sortOrders;

        if ($spellingType !== null) {
            $this->spellingType = $spellingType;
        }

        if ($trackTotalHits !== null) {
            $this->trackTotalHits = $this->parseTrackTotalHits($trackTotalHits);
        }

        if ($minScore !== null) {
            $this->minScore = $minScore;
        }
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
    public function getTrackTotalHits()
    {
        return $this->trackTotalHits;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinScore()
    {
        return $this->minScore;
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

    /**
     * {@inheritDoc}
     */
    public function getSpellingType()
    {
        return $this->spellingType;
    }

    /**
     * {@inheritDoc}
     */
    public function setCollapse(CollapseInterface $collapse)
    {
        $this->collapse = $collapse;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasCollapse()
    {
        return ($this->collapse instanceof CollapseInterface);
    }

    /**
     * {@inheritDoc}
     */
    public function getCollapse()
    {
        return $this->collapse;
    }

    /**
     * {@inheritDoc}
     */
    public function setSourceConfig($sourceConfig)
    {
        $this->sourceConfig = $sourceConfig;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSourceConfig()
    {
        return !empty($this->sourceConfig);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceConfig()
    {
        return $this->sourceConfig;
    }

    /**
     * Parse the track_total_hits directive to appropriate type : either int or bool.
     * It's actually passed as a string when coming from the configuration file reader.
     *
     * @param int|bool|string $trackTotalHits The track_total_hits value
     *
     * @return int|bool
     */
    private function parseTrackTotalHits($trackTotalHits)
    {
        // @codingStandardsIgnoreStart
        $trackTotalHits = is_numeric($trackTotalHits) ? (int) $trackTotalHits : filter_var($trackTotalHits, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        // @codingStandardsIgnoreEnd

        if ($trackTotalHits === false || $trackTotalHits === null) {
            $trackTotalHits = 0;
        }

        return $trackTotalHits;
    }
}
