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

namespace Smile\ElasticsuiteVirtualCategory\Plugin\Staging\Catalog;

use Smile\ElasticsuiteVirtualCategory\Model\Rule\CacheCleaner;

/**
 * Clean the virtual rules cache when scheduled updates of categories are applied (Adobe Commerce Content Staging).
 * Those updates are applied without saving the categories, so the category save plugin is not triggered.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class CategoryApplierPlugin
{
    /**
     * @var CacheCleaner
     */
    private $cacheCleaner;

    /**
     * Constructor.
     *
     * @param CacheCleaner $cacheCleaner Virtual rules cache cleaner.
     */
    public function __construct(CacheCleaner $cacheCleaner)
    {
        $this->cacheCleaner = $cacheCleaner;
    }

    /**
     * Clean the virtual rules cache entries depending on the updated categories or on their ancestors.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Magento\CatalogStaging\Model\CategoryApplier $subject   Staging category applier.
     * @param mixed                                         $result    Original method result.
     * @param array                                         $entityIds Updated category ids.
     * @return mixed
     */
    public function afterExecute($subject, $result, array $entityIds)
    {
        $this->cacheCleaner->cleanWithAncestors($entityIds);

        return $result;
    }
}
