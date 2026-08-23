<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Mapping helper test case.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
class MappingTest extends TestCase
{
    /**
     * Tests that getOptionTextFieldName returns a string with the 'option_text_' prefix prepended to the field name.
     *
     * @return void
     */
    public function testGetOptionTextFieldNameReturnsStringWithPrefixPrependedToFieldName()
    {
        $mockContext = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        $mapping = new \Smile\ElasticsuiteCore\Helper\Mapping($mockContext);
        $fieldName = 'test_field';
        $result = $mapping->getOptionTextFieldName($fieldName);

        $this->assertEquals('option_text_test_field', $result);
    }
}
