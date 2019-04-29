<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Test\Unit\Helper;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCatalog\Helper\AbstractAttribute;

/**
 * Attribute helper unit test.
 *
 * @category Smile_Elasticsuite
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class AbstractAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test automatic attribute ES field type detection.
     *
     * @dataProvider attributeTypeProvider
     *
     * @param string $backendType   Attribute backend type.
     * @param bool   $usesSource    Does attribute uses source.
     * @param string $sourceModel   Attribute source model
     * @param string $frontendClass Attribute frontend class.
     * @param string $expectedType  Expected ES field type.
     *
     * @return void
     */
    public function testFieldTypes($backendType, $usesSource, $sourceModel, $frontendClass, $expectedType)
    {
        $attributeMock = $this->createMock(\Magento\Catalog\Model\Entity\Attribute::class);
        $attributeMock->expects($this->any())->method('getBackendType')->will($this->returnValue($backendType));
        $attributeMock->method('usesSource')->will($this->returnValue($usesSource));
        $attributeMock->method('getSourceModel')->will($this->returnValue($sourceModel));
        $attributeMock->method('getFrontendClass')->will($this->returnValue($frontendClass));

        $helperMock = $this->getMockForAbstractClass(AbstractAttribute::class, [], '', false);
        $this->assertEquals($expectedType, $helperMock->getFieldType($attributeMock));
    }

    /**
     * List of tested combination for the getFieldType method.
     *
     * @return array
     */
    public function attributeTypeProvider()
    {
        return [
            ['int', true, 'Magento\Eav\Model\Entity\Attribute\Source\Boolean', null, FieldInterface::FIELD_TYPE_BOOLEAN],
            ['int', false, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            ['varchar', false, null, 'validate-digits', FieldInterface::FIELD_TYPE_LONG],
            ['decimal', false, null, null, FieldInterface::FIELD_TYPE_DOUBLE],
            ['varchar', false, null, 'validate-number', FieldInterface::FIELD_TYPE_DOUBLE],
            ['datetime', false, null, null, FieldInterface::FIELD_TYPE_DATE],
            ['varchar', true, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            ['varchar', false, null, null, FieldInterface::FIELD_TYPE_TEXT],
            ['varchar', true, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            ['varchar', true, 'sourceModel', null, FieldInterface::FIELD_TYPE_KEYWORD],
        ];
    }
}
