<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Test\Unit\Search\Request\Query;

use Smile\ElasticsuiteCore\Search\Request\Query\Term;

/**
 * Term search request query test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TermTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test default getters awaited values.
     *
     * @return void
     */
    public function testDefaultGetters()
    {
        $termQuery = new Term('value', 'field');
        $this->assertEquals(Term::TYPE_TERM, $termQuery->getType());
        $this->assertEquals('field', $termQuery->getField());
        $this->assertEquals('value', $termQuery->getValue());
        $this->assertEquals(Term::DEFAULT_BOOST_VALUE, $termQuery->getBoost());
        $this->assertNull($termQuery->getName());
    }

    /**
     * Test named queries behavior.
     *
     * @return void
     */
    public function testNamedQuery()
    {
        $termQuery = new Term('value', 'field', 'query_name');
        $this->assertEquals('query_name', $termQuery->getName());
    }

    /**
     * Test boosted queries behavior.
     *
     * @return void
     */
    public function testBoostedQuery()
    {
        $termQuery = new Term('value', 'field', null, 2);
        $this->assertEquals(2, $termQuery->getBoost());
    }
}
