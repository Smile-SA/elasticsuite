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

namespace Smile\ElasticSuiteCore\Search\Request;

interface QueryInterface extends \Magento\Framework\Search\Request\QueryInterface
{
    const DEFAULT_BOOST_VALUE = 1;

    const TYPE_NESTED       = 'nestedQuery'; //@todo
    const TYPE_RANGE        = 'rangeQuery';  //@todo
    const TYPE_TERM         = 'termQuery';
    const TYPE_TERMS        = 'termsQuery';
    const TYPE_MULTIMATCH   = 'multiMatchQuery';
    const TYPE_COMMON       = 'commonQuery';
    const TYPE_FULLTEXT     = 'fulltextQuery';
    const TYPE_AUTOCOMPLETE = 'autocompleteQuery';

    /*const TYPE_MATCH = 'matchQuery'; //@todo
    const TYPE_BOOL = 'boolQuery';
    const TYPE_FILTER = 'filteredQuery';*/
}