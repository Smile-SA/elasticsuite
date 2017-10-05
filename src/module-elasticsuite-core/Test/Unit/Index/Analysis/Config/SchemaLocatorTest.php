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
namespace Smile\ElasticsuiteCore\Test\Unit\Index\Analysis\Config;

use Smile\ElasticsuiteCore\Index\Analysis\Config\SchemaLocator;

/**
 * Analysis configuration xsd schema locator test case.
 *
 * @category  Smile_Elasticsuite
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class SchemaLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test the schema file path is correct.
     *
     * @return void
     */
    public function testSchemaFilePath()
    {
        $moduleReader = $this->getMockBuilder(\Magento\Framework\Module\Dir\Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $moduleReader->method('getModuleDir')
            ->will($this->returnValue('moduleDirectory'));

        $schemaLocator = new SchemaLocator($moduleReader);

        $this->assertEquals('moduleDirectory/elasticsuite_analysis.xsd', $schemaLocator->getPerFileSchema());
        $this->assertEquals('moduleDirectory/elasticsuite_analysis.xsd', $schemaLocator->getSchema());
    }
}
