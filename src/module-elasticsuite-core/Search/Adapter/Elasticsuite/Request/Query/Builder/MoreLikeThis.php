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

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\BuilderInterface;

/**
 * Build an ES mlt query.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class MoreLikeThis implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryInterface $query)
    {
        if ($query->getType() !== QueryInterface::TYPE_MORELIKETHIS) {
            throw new \InvalidArgumentException("Query builder : invalid query type {$query->getType()}");
        }

        $searchQueryParams = [
            'fields'               => $query->getFields(),
            'minimum_should_match' => $query->getMinimumShouldMatch(),
            'boost'                => $query->getBoost(),
            'like'                 => $query->getLike(),
            'boost_terms'          => $query->getBoostTerms(),
            'min_term_freq'        => $query->getMinTermFreq(),
            'min_doc_freq'         => $query->getMinDocFreq(),
            'max_doc_freq'         => $query->getMaxDocFreq(),
            'max_query_terms'      => $query->getMaxQueryTerms(),
            'min_word_length'      => $query->getMinWordLength(),
            'max_word_length'      => $query->getMaxWordLength(),
            'include'              => $query->includeOriginalDocs(),
        ];

        if ($query->getName()) {
            $searchQueryParams['_name'] = $query->getName();
        }

        return ['more_like_this' => $searchQueryParams];
    }
}
