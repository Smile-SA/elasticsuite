<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Define new usable query types in ElasticSuite.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface QueryInterface extends \Magento\Framework\Search\Request\QueryInterface
{
    const DEFAULT_BOOST_VALUE = 1;

    const TYPE_NESTED        = 'nestedQuery';
    const TYPE_RANGE         = 'rangeQuery';
    const TYPE_TERM          = 'termQuery';
    const TYPE_TERMS         = 'termsQuery';
    const TYPE_NOT           = 'notQuery';
    const TYPE_MULTIMATCH    = 'multiMatchQuery';
    const TYPE_COMMON        = 'commonQuery';
    const TYPE_MISSING       = 'missingQuery';
    const TYPE_FUNCTIONSCORE = 'functionScore';
}
