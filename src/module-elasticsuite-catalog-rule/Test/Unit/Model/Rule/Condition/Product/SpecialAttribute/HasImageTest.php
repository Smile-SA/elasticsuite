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

use Magento\Config\Model\Config\Source\Yesno;
use Smile\ElasticsuiteCatalogRule\Model\Rule\Condition\Product\SpecialAttribute\HasImage;
use Smile\ElasticsuiteCore\Search\Request\Query\Boolean;
use Smile\ElasticsuiteCore\Search\Request\Query\Exists;
use Smile\ElasticsuiteCore\Search\Request\Query\Not;
use Smile\ElasticsuiteCore\Search\Request\Query\Terms;
use Smile\ElasticsuiteCore\Search\Request\QueryInterface;

/**
 * Has Image special attribute rule unit test.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogRule
 */
class HasImageTest extends AbstractSpecialAttribute
{
    /**
     * Test default methods returns.
     *
     * @return void
     */
    public function testDefaultReturns()
    {
        $booleanSource = new Yesno();
        $hasImage = new HasImage($this->getQueryFactory(), $booleanSource);

        $this->assertEquals('has_image', $hasImage->getAttributeCode());
        $this->assertEquals(' ', $hasImage->getOperatorName());
        $this->assertEquals('select', $hasImage->getInputType());
        $this->assertEquals('hidden', $hasImage->getValueElementType());
        $this->assertEquals(' ', $hasImage->getValueName('randomValue'));
        $this->assertEquals(true, $hasImage->getValue('randomValue'));
        $this->assertEquals($booleanSource->toOptionArray(), $hasImage->getValueOptions());
        $this->assertEquals(__('Only products with image'), $hasImage->getLabel());
    }

    /**
     * Test search query building.
     *
     * @return void
     */
    public function testGetSearchQuery()
    {
        $booleanSource = new Yesno();
        $hasImage = new HasImage($this->getQueryFactory(), $booleanSource);

        $searchQuery = $hasImage->getSearchQuery($this->getProductConditionMock());
        $this->assertInstanceOf(QueryInterface::class, $searchQuery);
        $this->assertInstanceOf(Boolean::class, $searchQuery);
        $this->assertEquals(QueryInterface::TYPE_BOOL, $searchQuery->getType());

        /** @var Boolean $searchQuery */
        $this->assertEmpty($searchQuery->getShould());
        $this->assertEmpty($searchQuery->getMustNot());
        $this->assertCount(2, $searchQuery->getMust());
        $mustClauses = $searchQuery->getMust();

        $this->assertInstanceOf(Exists::class, $mustClauses[0]);
        $clause = $mustClauses[0];
        /** @var Exists $clause */
        $this->assertEquals('image', $clause->getField());

        $this->assertInstanceOf(Not::class, $mustClauses[1]);
        $clause = $mustClauses[1];
        /** @var Not $clause */
        $this->assertInstanceOf(Terms::class, $clause->getQuery());
        /** @var Terms $termsQuery */
        $termsQuery = $clause->getQuery();
        $this->assertEquals('image', $termsQuery->getField());
        $this->assertEquals(['no_selection'], $termsQuery->getValues());
    }
}
