<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Richard Bayet <richard.bayet@smile.fr>
 * @copyright 2026 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteVirtualCategory\Test\Unit\Model\Rule;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Smile\ElasticsuiteVirtualCategory\Model\Rule\RootCategoryProvider;

/**
 * Unit test for {@see RootCategoryProvider}.
 *
 * Covered scenarios:
 * - A root category is loaded once per store, whatever the number of virtual categories using it
 * - Reset forgets the loaded categories
 * - A missing category raises an exception, and is not kept
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class RootCategoryProviderTest extends TestCase
{
    /**
     * @var array[] Loads done by the category factory mock: [category id, store id].
     */
    private $loads = [];

    /**
     * Test that a root category is loaded once per store.
     *
     * @return void
     */
    public function testLoadedOncePerStore(): void
    {
        $provider = $this->createProvider([4025]);

        // "Tops" (4025) used as virtual root by 12 subset subcategories.
        for ($subcategory = 0; $subcategory < 12; $subcategory++) {
            $this->assertSame(4025, (int) $provider->get(4025, 1)->getId());
        }
        $provider->get(4025, 2);
        $provider->get(4025);

        $this->assertSame([[4025, 1], [4025, 2], [4025, null]], $this->loads);
    }

    /**
     * Test that reset forgets the loaded categories.
     *
     * @return void
     */
    public function testReset(): void
    {
        $provider = $this->createProvider([4025]);
        $provider->get(4025, 1);
        $provider->reset();
        $provider->get(4025, 1);

        $this->assertCount(2, $this->loads);
    }

    /**
     * Test that a missing category raises an exception each time it is requested.
     *
     * @return void
     */
    public function testMissingCategory(): void
    {
        $provider = $this->createProvider([]);

        foreach ([1, 2] as $attempt) {
            try {
                $provider->get(999, 1);
                $this->fail('An exception was expected.');
            } catch (NoSuchEntityException $exception) {
                $this->assertCount($attempt, $this->loads);
            }
        }
    }

    /**
     * Create a provider whose category factory only knows the given category ids.
     *
     * @param int[] $existingIds Existing category ids.
     *
     * @return RootCategoryProvider
     */
    private function createProvider(array $existingIds): RootCategoryProvider
    {
        $factory = $this->createMock(CategoryFactory::class);
        $factory->method('create')->willReturnCallback(
            function () use ($existingIds) {
                $category = $this->getMockBuilder(Category::class)->disableOriginalConstructor()
                    ->onlyMethods(['load', 'setStoreId', 'getId'])->getMock();
                $state = ['id' => null, 'storeId' => null];
                $category->method('setStoreId')->willReturnCallback(
                    function ($storeId) use ($category, &$state) {
                        $state['storeId'] = $storeId;

                        return $category;
                    }
                );
                $category->method('load')->willReturnCallback(
                    function ($categoryId) use ($category, &$state, $existingIds) {
                        $this->loads[] = [$categoryId, $state['storeId']];
                        $state['id']   = in_array($categoryId, $existingIds, true) ? $categoryId : null;

                        return $category;
                    }
                );
                $category->method('getId')->willReturnCallback(
                    function () use (&$state) {
                        return $state['id'];
                    }
                );

                return $category;
            }
        );

        return new RootCategoryProvider($factory);
    }
}
