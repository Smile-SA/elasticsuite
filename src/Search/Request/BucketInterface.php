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

/**
 * Define new usable bucket types in ElasticSuite.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BucketInterface extends \Magento\Framework\Search\Request\BucketInterface
{
    const TYPE_HISTOGRAM      = 'histogramBucket';
    const TYPE_DATE_HISTOGRAM = 'dateHistogramBucket';
    const TYPE_FILTER         = 'filterBucket';
    const TYPE_FILTER_GROUP   = 'filterGroupBucket';
    const TYPE_NESTED         = 'nestedBucker';
}
