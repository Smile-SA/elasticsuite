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
 * Extension of Magento default bucket interface :
 *
 * - Define new usable bucket types in ElasticSuite (histogrrams)
 * - Additional methods to handle nested and filtered aggregations
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface BucketInterface extends \Magento\Framework\Search\Request\BucketInterface
{
    const TYPE_HISTOGRAM      = 'histogramBucket';
    const TYPE_DATE_HISTOGRAM = 'dateHistogramBucket';

    /**
     * Indicates if the aggregation is nested.
     *
     * @return @boolean
     */
    public function isNested();

    /**
     * Nested path for nested aggregations.
     *
     * @return string
     */
    public function getNestedPath();

    /**
     * Optional filter for filtered aggregations.
     *
     * @return QueryInterface|null
     */
    public function getFilter();
}
