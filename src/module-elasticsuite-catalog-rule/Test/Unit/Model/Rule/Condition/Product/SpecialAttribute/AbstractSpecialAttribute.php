<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogRule
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogRule\Test\Unit\Model\Rule\Condition\Product\SpecialAttribute;

use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Common;
use Smile\ElasticsuiteCore\Search\Request\Query\Exists;
use Smile\ElasticsuiteCore\Search\Request\Query\Filtered;
use Smile\ElasticsuiteCore\Search\Request\Query\FunctionScore;
use Smile\ElasticsuiteCore\Search\Request\Query\MatchPhrasePrefix;
use Smile\ElasticsuiteCore\Search\Request\Query\MatchQuery;
use Smile\ElasticsuiteCore\Search\Request\Query\Missing;
use Smile\ElasticsuiteCore\Search\Request\Query\MoreLikeThis;
use Smile\ElasticsuiteCore\Search\Request\Query\MultiMatch;
use Smile\ElasticsuiteCore\Search\Request\Query\Nested;
use Smile\ElasticsuiteCore\Search\Request\Query\Not;
use Smile\ElasticsuiteCore\Search\Request\Query\Prefix;
use Smile\ElasticsuiteCore\Search\Request\Query\QueryFactory;
use Smile\ElasticsuiteCore\Search\Request\Query\Range;
use Smile\ElasticsuiteCore\Search\Request\Query\Regexp;
use Smile\ElasticsuiteCore\Search\Request\Query\Term;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Abstract special attribute unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
abstract class AbstractSpecialAttribute extends TestCase
{
    /**
     * @var array
     */
    protected $queryTypes = [
        QueryInterface::TYPE_MATCH  => MatchQuery::class,
        QueryInterface::TYPE_BOOL   => Boolean::class,
        QueryInterface::TYPE_FILTER => Filtered::class,
        QueryInterface::TYPE_NESTED => Nested::class,
        QueryInterface::TYPE_RANGE  => Range::class,
        QueryInterface::TYPE_TERM   => Term::class,
        QueryInterface::TYPE_TERMS  => Terms::class,
        QueryInterface::TYPE_NOT    => Not::class,
        QueryInterface::TYPE_MULTIMATCH => MultiMatch::class,
        QueryInterface::TYPE_COMMON     => Common::class,
        QueryInterface::TYPE_EXISTS     => Exists::class,
        QueryInterface::TYPE_MISSING    => Missing::class,
        QueryInterface::TYPE_FUNCTIONSCORE  => FunctionScore::class,
        QueryInterface::TYPE_MORELIKETHIS   => MoreLikeThis::class,
        QueryInterface::TYPE_MATCHPHRASEPREFIX => MatchPhrasePrefix::class,
        QueryInterface::TYPE_PREFIX => Prefix::class,
        QueryInterface::TYPE_REGEXP => Regexp::class,
    ];

    /**
     * Mock the query factory used by the builder.
     *
     * @return QueryFactory
     */
    protected function getQueryFactory()
    {
        $factories = [];

        foreach ($this->queryTypes as $currentType => $queryClass) {
            $queryCreateCallback = function ($queryParams) use ($queryClass) {
                return new $queryClass(...$queryParams);
            };
            $factory = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();

            $factory->method('create')->willReturnCallback($queryCreateCallback);

            $factories[$currentType] = $factory;
        }

        return new QueryFactory($factories);
    }

    /**
     * Get Product Condition mock object.
     *
     * @return Product|MockObject
     */
    protected function getProductConditionMock()
    {
        return $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
    }
}
