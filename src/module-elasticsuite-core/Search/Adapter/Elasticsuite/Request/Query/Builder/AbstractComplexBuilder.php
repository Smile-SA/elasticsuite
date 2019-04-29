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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Request\Query\Builder;

/**
 * Complex builder are able to used the global builder to build subqueries.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractComplexBuilder
{
    /**
     * @var Builder
     */
    protected $parentBuilder;

    /**
     * Constructor.
     *
     * @param Builder $builder Parent builder used to build subqueries.
     */
    public function __construct(Builder $builder)
    {
        $this->parentBuilder = $builder;
    }
}
