<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteThesaurus\Test\Unit\Model\ResourceModel\Thesaurus;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteThesaurus\Model\ResourceModel\Thesaurus\Collection;

/**
 * Thesaurus resource collection unit tests.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Richard BAYET <richard.bayet@smile.fr>
 */
#[AllowMockObjectsWithoutExpectations]
class CollectionTest extends TestCase
{
    /**
     * The term filter value must be escaped for LIKE wildcards and bound as a query parameter,
     * never interpolated into the condition string. The deliberate whitespace/hyphen-to-wildcard
     * substitution must still be applied, after the escaping.
     */
    public function testSetTermFilterEscapesAndBindsTheValue()
    {
        $dbHelper = $this->createMock(DbHelper::class);
        $dbHelper->expects($this->once())
            ->method('escapeLikeValue')
            ->with("o'brien-%_")
            ->willReturn('safe value');

        $select = $this->createMock(Select::class);
        $select->method('joinLeft')->willReturnSelf();
        $select->method('group')->willReturnSelf();

        // The bound value is the escaped output ('safe value'), with its space turned into a
        // wildcard, wrapped in '%...%' — and it is passed as a bound parameter to "LIKE ?".
        $select->expects($this->once())
            ->method('where')
            ->with('expansion_table.term LIKE ?', '%safe%value%')
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('orWhere')
            ->with('reference_table.term LIKE ?', '%safe%value%')
            ->willReturnSelf();

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'getTable'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getTable')->willReturnArgument(0);

        $this->injectResourceHelper($collection, $dbHelper);

        $collection->setTermFilter("o'brien-%_");
    }

    /**
     * Inject the private resource helper dependency (constructor is bypassed in this unit test).
     *
     * @param Collection $collection The collection under test.
     * @param DbHelper   $dbHelper   The DB helper mock to inject.
     *
     * @return void
     */
    private function injectResourceHelper(Collection $collection, DbHelper $dbHelper)
    {
        $property = new \ReflectionProperty(Collection::class, 'resourceHelper');
        $property->setValue($collection, $dbHelper);
    }
}
