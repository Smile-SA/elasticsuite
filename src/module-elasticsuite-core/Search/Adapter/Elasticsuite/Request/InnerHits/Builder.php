<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\InnerHits;

use Smile\ElasticsuiteCore\Search\Request\InnerHitsInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\SortOrder\Builder as SortOrderBuilder;

/**
 * Query inner hits builder.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class Builder
{
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * Builder constructor.
     *
     * @param SortOrderBuilder $sortOrderBuilder Sort order builder.
     */
    public function __construct(SortOrderBuilder $sortOrderBuilder)
    {
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Build an inner hits definition into an ES inner hits section.
     *
     * @param InnerHitsInterface $innerHitsConfig Inner hits.
     *
     * @return array
     */
    public function buildInnerHits(InnerHitsInterface $innerHitsConfig)
    {
        $innerHits = [
            'name' => $innerHitsConfig->getName(),
            'from' => $innerHitsConfig->getFrom(),
            'size' => $innerHitsConfig->getSize(),
        ];

        $sortOrders = $this->sortOrderBuilder->buildSortOrders($innerHitsConfig->getSort());
        if (!empty($sortOrders)) {
            $innerHits['sort'] = $sortOrders;
        }

        return $innerHits;
    }
}
