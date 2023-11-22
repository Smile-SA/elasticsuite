<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\DataProvider;

use Magento\Search\Model\QueryInterface;
use Magento\AdvancedSearch\Model\SuggestedQueriesInterface;

/**
 * Elasticsuite Search Suggestions.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class Suggestions implements SuggestedQueriesInterface
{
    /**
     * {@inheritdoc}
     */
    public function isResultsCountEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(QueryInterface $query)
    {
        return [];
    }
}
