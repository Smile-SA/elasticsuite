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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Collapse;

use Smile\ElasticsuiteCore\Search\Request\CollapseInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\InnerHits\Builder as InnerHitsBuilder;

/**
 * Build the part of the ES request collapsing search results
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 */
class Builder
{
    /**
     * @var InnerHitsBuilder
     */
    private $innerHitsBuilder;

    /**
     * Builder constructor.
     *
     * @param InnerHitsBuilder $innerHitsBuilder Inner hits builder.
     */
    public function __construct(InnerHitsBuilder $innerHitsBuilder)
    {
        $this->innerHitsBuilder = $innerHitsBuilder;
    }

    /**
     * Build the ES collapse section of the request from a Collapse
     *
     * @param CollapseInterface $collapseConfig Collapse configuration
     *
     * @return array
     */
    public function buildCollapse(CollapseInterface $collapseConfig)
    {
        $collapse = [
            'field' => $collapseConfig->getField(),
        ];

        $innerHits = [];
        foreach ($collapseConfig->getInnerHits() as $innerHitsConfig) {
            $innerHits[] = $this->innerHitsBuilder->buildInnerHits($innerHitsConfig);
        }
        if (!empty($innerHits)) {
            $collapse['inner_hits'] = $innerHits;
        }

        return $collapse;
    }
}
