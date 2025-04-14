<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Test\Unit\Helper;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCatalog\Helper\AbstractAttribute;
use Smile\ElasticsuiteCore\Api\Index\MappingInterface;
use \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as AttributeFactory;

/**
 * Attribute helper unit test.
 *
 * @category Smile
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
     * @param int    $attributeId   Attribute id.
     * @param string $backendType   Attribute backend type.
     * @param bool   $usesSource    Does attribute uses source.
     * @param string $sourceModel   Attribute source model
     * @param string $frontendClass Attribute frontend class.
     * @param string $expectedType  Expected ES field type.
     *
     * @return void
     */
    public function testFieldTypes($attributeId, $backendType, $usesSource, $sourceModel, $frontendClass, $expectedType)
    {
        $contextMock   = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        $attributeMock = $this->createMock(\Magento\Catalog\Model\Entity\Attribute::class);

        $attributeMock->expects($this->any())->method('getBackendType')->will($this->returnValue($backendType));
        $attributeMock->method('usesSource')->will($this->returnValue($usesSource));
        $attributeMock->method('getId')->will($this->returnValue($attributeId));
        $attributeMock->method('getSourceModel')->will($this->returnValue($sourceModel));
        $attributeMock->method('getFrontendClass')->will($this->returnValue($frontendClass));

        $attributeFactoryMock = $this->getMockBuilder('\Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $attributeFactoryMock->method('create')->will(($this->returnValue($attributeMock)));

        $helperMock = $this->getMockForAbstractClass(
            AbstractAttribute::class,
            [$contextMock, $attributeFactoryMock, null],
            '',
            true,
            true,
            true,
            ['getAttributeById']
        );

        $this->assertEquals($expectedType, $helperMock->getFieldType($attributeMock->getId()));
    }

    /**
     * List of tested combination for the getFieldType method.
     *
     * @return array
     */
    public function attributeTypeProvider()
    {
        return [
            [1, 'int', true, 'Magento\Eav\Model\Entity\Attribute\Source\Boolean', null, FieldInterface::FIELD_TYPE_BOOLEAN],
            [2, 'int', false, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            [3, 'varchar', false, null, 'validate-digits', FieldInterface::FIELD_TYPE_LONG],
            [4, 'decimal', false, null, null, FieldInterface::FIELD_TYPE_DOUBLE],
            [5, 'varchar', false, null, 'validate-number', FieldInterface::FIELD_TYPE_DOUBLE],
            [6, 'datetime', false, null, null, FieldInterface::FIELD_TYPE_DATE],
            [7, 'varchar', true, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            [8, 'varchar', false, null, null, FieldInterface::FIELD_TYPE_TEXT],
            [9, 'varchar', true, null, null, FieldInterface::FIELD_TYPE_INTEGER],
            [10, 'varchar', true, 'sourceModel', null, FieldInterface::FIELD_TYPE_KEYWORD],
        ];
    }
}
