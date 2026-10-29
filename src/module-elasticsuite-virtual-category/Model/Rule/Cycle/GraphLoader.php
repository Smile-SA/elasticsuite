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
use Magento\Framework\Serialize\Serializer\Json;
use Smile\ElasticsuiteVirtualCategory\Helper\Config;

/**
 * Loads the data needed to detect the cycles of virtual categories: the path of every category, whether it is used as
 * a purely physical category by the other categories (boundary categories, and the store roots when configured so),
 * and the virtual root and the categories referenced by the rule of every virtual category.
 *
 * The virtual category attributes being global, the default values are used. The categories status, which can be
 * set per store, is ignored: a disabled category can be enabled again.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class GraphLoader
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor.
     *
     * @param CollectionFactory $categoryCollectionFactory Category collection factory.
     * @param Json              $jsonSerializer            JSON serializer.
     * @param Config            $config                    Virtual category configuration.
     */
    public function __construct(CollectionFactory $categoryCollectionFactory, Json $jsonSerializer, Config $config)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->jsonSerializer            = $jsonSerializer;
        $this->config                    = $config;
    }

    /**
     * Load the categories, as expected by {@see Detector::findCycles()}.
     *
     * @return array
     */
    public function load(): array
    {
        $categories  = [];
        $boundaryIds = array_flip($this->config->getVirtualRulesExpansionBoundaries());
        // The store roots (level 1) are used as physical categories by the categories of their own tree.
        $storeRoots  = $this->config->isStoreRootUsedAsStandardCategory();

        $collection = $this->categoryCollectionFactory->create()->setStoreId(0);
        foreach ($collection as $category) {
            $categoryId = (int) $category->getId();
            $path       = array_map('intval', explode('/', (string) $category->getPath()));
            $categories[$categoryId] = [
                'path'     => $path,
                'virtual'  => false,
                'root'     => null,
                'refs'     => [],
                'physical' => isset($boundaryIds[$categoryId]) || ($storeRoots && count($path) === 2),
            ];
        }

        $virtualCollection = $this->categoryCollectionFactory->create()->setStoreId(0)
            ->addAttributeToFilter('is_virtual_category', 1)
            ->addAttributeToSelect(['virtual_category_root', 'virtual_rule']);
        foreach ($virtualCollection as $category) {
            $categoryId = (int) $category->getId();
            if (isset($categories[$categoryId])) {
                $categories[$categoryId]['virtual'] = true;
                $categories[$categoryId]['root']    = ((int) $category->getVirtualCategoryRoot()) ?: null;
                $categories[$categoryId]['refs']    = $this->getReferencedCategoryIds($category->getVirtualRule());
            }
        }

        return $categories;
    }

    /**
     * Ids of the categories referenced by the category_ids conditions of a virtual rule, whatever its operator.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param mixed $virtualRule Virtual rule, as stored (JSON) or loaded.
     *
     * @return int[]
     */
    private function getReferencedCategoryIds($virtualRule): array
    {
        if (is_object($virtualRule) && method_exists($virtualRule, 'getConditions')) {
            $conditions = $virtualRule->getConditions()->asArray();
        } elseif (is_string($virtualRule) && $virtualRule !== '') {
            try {
                $conditions = $this->jsonSerializer->unserialize($virtualRule);
            } catch (\InvalidArgumentException $exception) {
                $conditions = [];
            }
        } else {
            $conditions = [];
        }

        $categoryIds = [];
        $this->collectReferencedCategoryIds((array) $conditions, $categoryIds);

        return array_values(array_unique($categoryIds));
    }

    /**
     * Collect recursively the category ids of the category_ids conditions.
     *
     * @param array $condition   Condition, as an array.
     * @param int[] $categoryIds Collected category ids.
     *
     * @return void
     */
    private function collectReferencedCategoryIds(array $condition, array &$categoryIds): void
    {
        if (($condition['attribute'] ?? null) === 'category_ids') {
            $values = $condition['value'] ?? [];
            $values = is_array($values) ? $values : explode(',', (string) $values);
            foreach ($values as $value) {
                if (is_numeric(trim((string) $value))) {
                    $categoryIds[] = (int) trim((string) $value);
                }
            }
        }

        foreach ((array) ($condition['conditions'] ?? []) as $subCondition) {
            if (is_array($subCondition)) {
                $this->collectReferencedCategoryIds($subCondition, $categoryIds);
            }
        }
    }
}
