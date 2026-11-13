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

namespace Smile\ElasticsuiteVirtualCategory\Model\Rule\Cycle;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Finds the cycles of virtual categories of the catalog and describes them.
 *
 * Within a cycle, a category query cannot fully rely on the query of the categories it relies on: they are left out
 * of it while being built, which may lead to unexpected products.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Checker
{
    /**
     * @var GraphLoader
     */
    private $graphLoader;

    /**
     * @var Detector
     */
    private $detector;

    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * Constructor.
     *
     * @param GraphLoader       $graphLoader               Cycles graph loader.
     * @param Detector          $detector                  Cycles detector.
     * @param CollectionFactory $categoryCollectionFactory Category collection factory.
     */
    public function __construct(
        GraphLoader $graphLoader,
        Detector $detector,
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->graphLoader               = $graphLoader;
        $this->detector                  = $detector;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Cycles of virtual categories of the catalog.
     *
     * @param int[]|null $throughIds Only return the cycles going through at least one of these categories.
     *
     * @return array[] One item per group of interdependent categories: 'path' (category ids of a cycle, starting
     *                 and ending with the same category), 'size' (number of interdependent categories of the group),
     *                 'members' (their ids, sorted) and 'label' (eg. "Women (12) > Women Picks (40) > Summer (75) >
     *                 Women (12)").
     */
    public function getCycles(?array $throughIds = null): array
    {
        $cycles = $this->detector->findCycles($this->graphLoader->load(), $throughIds);
        $names  = $this->getNames(empty($cycles) ? [] : array_unique(array_merge(...array_column($cycles, 'path'))));

        foreach ($cycles as &$cycle) {
            $labels = array_map(function ($categoryId) use ($names) {
                return sprintf('%s (%d)', $names[$categoryId] ?? '', $categoryId);
            }, $cycle['path']);
            $cycle['label'] = implode(' > ', $labels);
        }

        return $cycles;
    }

    /**
     * Default names of some categories.
     *
     * @param int[] $categoryIds Category ids.
     *
     * @return string[]
     */
    private function getNames(array $categoryIds): array
    {
        $names = [];

        if (!empty($categoryIds)) {
            $collection = $this->categoryCollectionFactory->create()->setStoreId(0)
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
            foreach ($collection as $category) {
                $names[(int) $category->getId()] = (string) $category->getName();
            }
        }

        return $names;
    }
}
