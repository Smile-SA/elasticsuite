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

/**
 * Detects the cycles of virtual categories: categories whose query relies, directly or not, on their own query.
 *
 * The dependencies followed are the ones followed when building a category query:
 * - a virtual category relies on the categories referenced by its rule (category_ids conditions),
 * - a virtual category relies on its virtual root, unless it is the root of the tree or above,
 * - any category relies on its virtual descendants, except the ones using it (or one of its descendants) as
 *   virtual root: those are left out of its query, all their products being already part of it.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class Detector
{
    /**
     * Find the cycles of a set of categories.
     *
     * @param array      $categories Categories by id: ['path' => int[], 'virtual' => bool, 'root' => ?int,
     *                               'refs' => int[], 'physical' => bool], the path including the category itself.
     *                               A "physical" category (boundary category, store root) is reduced to its own
     *                               products when used by another category: no dependency leads to it.
     * @param int[]|null $throughIds Only return the cycles going through at least one of these categories.
     *
     * @return array[] One item per group of interdependent categories: 'path' (the shortest cycle path from its
     *                 first category of $throughIds, or from its smallest id, starting and ending with the same
     *                 category id), 'size' (number of interdependent categories of the group) and 'members'
     *                 (their ids, sorted).
     */
    public function findCycles(array $categories, ?array $throughIds = null): array
    {
        $edges  = $this->getEdges($categories);
        $cycles = [];

        foreach ($this->getStronglyConnectedComponents($edges) as $component) {
            $members = array_flip($component);
            $isCycle = count($component) > 1 || in_array($component[0], $edges[$component[0]] ?? [], true);
            if (!$isCycle) {
                continue;
            }

            $starts = ($throughIds === null) ? [min($component)] : array_values(
                array_filter(array_map('intval', $throughIds), function ($categoryId) use ($members) {
                    return isset($members[$categoryId]);
                })
            );
            if (!empty($starts)) {
                sort($component);
                $cycles[] = [
                    'path'    => $this->getShortestCyclePath($edges, $members, $starts[0]),
                    'size'    => count($component),
                    'members' => $component,
                ];
            }
        }

        return $cycles;
    }

    /**
     * Dependencies of each category.
     *
     * @param array $categories Categories by id.
     *
     * @return int[][]
     */
    private function getEdges(array $categories): array
    {
        $rootPaths = [];
        foreach (array_keys($categories) as $categoryId) {
            $rootPaths[$categoryId] = $this->getEffectiveRootPath($categories, $categoryId);
        }
        $virtualDescendants = $this->getVirtualDescendants($categories);

        $edges = [];
        foreach ($categories as $categoryId => $category) {
            $targets = $category['virtual'] ? $category['refs'] : [];
            if (!empty($rootPaths[$categoryId])) {
                $targets[] = end($rootPaths[$categoryId]);
            }
            foreach ($virtualDescendants[$categoryId] ?? [] as $descendantId) {
                // Left out of the category query if using it (or one of its descendants) as virtual root.
                if (!in_array($categoryId, $rootPaths[$descendantId], true)) {
                    $targets[] = $descendantId;
                }
            }
            $edges[$categoryId] = array_values(array_unique(array_filter(
                array_map('intval', $targets),
                function ($targetId) use ($categories) {
                    return isset($categories[$targetId]) && empty($categories[$targetId]['physical']);
                }
            )));
        }

        return $edges;
    }

    /**
     * Virtual descendants of each category.
     *
     * @param array $categories Categories by id.
     *
     * @return int[][]
     */
    private function getVirtualDescendants(array $categories): array
    {
        $virtualDescendants = [];

        foreach ($categories as $categoryId => $category) {
            if ($category['virtual']) {
                foreach (array_slice($category['path'], 0, -1) as $ancestorId) {
                    $virtualDescendants[$ancestorId][] = $categoryId;
                }
            }
        }

        return $virtualDescendants;
    }

    /**
     * Path of the virtual root actually used by a virtual category (empty if none): the root must exist and be
     * below the root of the category tree.
     *
     * @param array $categories Categories by id.
     * @param int   $categoryId Category id.
     *
     * @return int[]
     */
    private function getEffectiveRootPath(array $categories, int $categoryId): array
    {
        $category = $categories[$categoryId];
        $rootId   = (int) ($category['root'] ?? 0);

        if (!$category['virtual'] || !$rootId || !isset($categories[$rootId])) {
            return [];
        }

        $rootPath     = $categories[$rootId]['path'];
        $treeRootId   = (int) ($category['path'][1] ?? 0);
        $isAboveTree  = count($rootPath) < 2 || $rootId === $treeRootId;

        return $isAboveTree ? [] : $rootPath;
    }

    /**
     * Strongly connected components of the dependencies graph (iterative Tarjan algorithm).
     *
     * @param int[][] $edges Dependencies by category id.
     *
     * @return int[][]
     */
    private function getStronglyConnectedComponents(array $edges): array
    {
        $state = ['index' => [], 'lowLink' => [], 'onStack' => [], 'stack' => [], 'components' => [], 'counter' => 0];

        foreach (array_keys($edges) as $startId) {
            if (!isset($state['index'][$startId])) {
                $this->visit($edges, $startId, $state);
            }
        }

        return $state['components'];
    }

    /**
     * Visit the categories reachable from a category not visited yet, iteratively (depth-first).
     *
     * @param int[][] $edges   Dependencies by category id.
     * @param int     $startId Category id.
     * @param array   $state   Tarjan algorithm state.
     *
     * @return void
     */
    private function visit(array $edges, int $startId, array &$state): void
    {
        // Each work item: [category id, position of the next dependency to visit].
        $work = [[$startId, 0]];

        while (!empty($work)) {
            [$categoryId, $position] = array_pop($work);
            if ($position === 0) {
                $state['index'][$categoryId] = $state['lowLink'][$categoryId] = $state['counter']++;
                $state['stack'][]            = $categoryId;
                $state['onStack'][$categoryId] = true;
            }

            $targets = $edges[$categoryId] ?? [];
            if ($position < count($targets)) {
                $work[]   = [$categoryId, $position + 1];
                $targetId = $targets[$position];
                if (!isset($state['index'][$targetId])) {
                    $work[] = [$targetId, 0];
                } elseif (isset($state['onStack'][$targetId])) {
                    $state['lowLink'][$categoryId] = min($state['lowLink'][$categoryId], $state['index'][$targetId]);
                }
                continue;
            }

            // All the dependencies of the category have been visited.
            $this->completeVisit($categoryId, $state);
            if (!empty($work)) {
                $parentId                    = end($work)[0];
                $state['lowLink'][$parentId] = min($state['lowLink'][$parentId], $state['lowLink'][$categoryId]);
            }
        }
    }

    /**
     * Once all the dependencies of a category visited, collect its component if it is the root of one.
     *
     * @param int   $categoryId Category id.
     * @param array $state      Tarjan algorithm state.
     *
     * @return void
     */
    private function completeVisit(int $categoryId, array &$state): void
    {
        if ($state['lowLink'][$categoryId] !== $state['index'][$categoryId]) {
            return;
        }

        $component = [];
        do {
            $memberId = array_pop($state['stack']);
            unset($state['onStack'][$memberId]);
            $component[] = $memberId;
        } while ($memberId !== $categoryId);

        $state['components'][] = $component;
    }

    /**
     * Shortest cycle path from a category back to itself, within its strongly connected component (breadth-first).
     *
     * @param int[][] $edges      Dependencies by category id.
     * @param array   $members    Ids of the component members, as keys.
     * @param int     $categoryId Category id.
     *
     * @return int[]
     */
    private function getShortestCyclePath(array $edges, array $members, int $categoryId): array
    {
        $previous = [];
        $queue    = [$categoryId];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            foreach ($edges[$currentId] ?? [] as $targetId) {
                if (!isset($members[$targetId])) {
                    continue;
                }
                if ($targetId === $categoryId) {
                    $path = [$categoryId];
                    for ($id = $currentId; $id !== $categoryId; $id = $previous[$id]) {
                        array_unshift($path, $id);
                    }
                    array_unshift($path, $categoryId);

                    return $path;
                }
                if (!isset($previous[$targetId])) {
                    $previous[$targetId] = $currentId;
                    $queue[]             = $targetId;
                }
            }
        }

        return [$categoryId, $categoryId];
    }
}
