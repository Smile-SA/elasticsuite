<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Define span query types usable in ElasticSuite.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface SpanQueryInterface extends QueryInterface
{
    const TYPE_SPAN_CONTAINING    = 'spanContainingQuery';
    const TYPE_SPAN_FIELD_MASKING = 'spanFieldMaskingQuery';
    const TYPE_SPAN_FIRST         = 'spanFirstQuery';
    const TYPE_SPAN_MULTI_TERM    = 'spanMultiTermQuery';
    const TYPE_SPAN_NEAR          = 'spanNearQuery';
    const TYPE_SPAN_NOT           = 'spanNotQuery';
    const TYPE_SPAN_OR            = 'spanOrQuery';
    const TYPE_SPAN_TERM          = 'spanTermQuery';
    const TYPE_SPAN_WITHIN        = 'spanWithinQuery';
}
