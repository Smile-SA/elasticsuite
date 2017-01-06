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

use Smile\ElasticsuiteCore\Search\Request\Query\Terms;

/**
 * Term search request query test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class TermsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test default getters awaited values.
     *
     * @return void
     */
    public function testDefaultGetters()
    {
        $termsQuery = new Terms('value', 'field');
        $this->assertEquals('field', $termsQuery->getField());
        $this->assertEquals(Terms::TYPE_TERMS, $termsQuery->getType());
        $this->assertEquals(Terms::DEFAULT_BOOST_VALUE, $termsQuery->getBoost());
        $this->assertNull($termsQuery->getName());
    }

    /**
     * Test different syntax of value setting and results.
     *
     * @return void
     */
    public function testValueGetter()
    {
        $termsQuery = new Terms('value', 'field');
        $this->assertEquals(['value'], $termsQuery->getValue());

        $termsQuery = new Terms('value1,value2', 'field');
        $this->assertEquals(['value1', 'value2'], $termsQuery->getValue());

        $termsQuery = new Terms(['value'], 'field');
        $this->assertEquals(['value'], $termsQuery->getValue());

        $termsQuery = new Terms(['value1', 'value2'], 'field');
        $this->assertEquals(['value1', 'value2'], $termsQuery->getValue());
    }
}
