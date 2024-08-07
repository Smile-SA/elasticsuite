<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form\Modifier;

/**
 * Optimizer Ui Component Modifier.
 * Used to populate search queries dynamicRows.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Limitation implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface
     */
    private $locator;

    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation
     */
    private $resource;

    /**
     * Search Terms constructor.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator  Optimizer Locator
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $resource Limitation Resource
     */
    public function __construct(
        \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\Locator\LocatorInterface $locator,
        \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Limitation $resource
    ) {
        $this->locator  = $locator;
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $optimizer = $this->locator->getOptimizer();

        if ($optimizer && $optimizer->getId() && isset($data[$optimizer->getId()])) {
            $searchContainers = $optimizer->getSearchContainers();

            $data[$optimizer->getId()]['search_container'] = array_keys($searchContainers);

            $applyToCategories = (int) ($searchContainers['catalog_view_container'] ?? 0);
            if ($applyToCategories) {
                $containerData = ['apply_to' => $applyToCategories];
                $categoryIds   = $this->resource->getCategoryIdsByOptimizer($optimizer);
                if (!empty($categoryIds)) {
                    $containerData['category_ids'] = $categoryIds;
                }
                $data[$optimizer->getId()]['catalog_view_container'] = $containerData;
            }

            // @codingStandardsIgnoreStart
            $applyToQueries = (bool) ($searchContainers['quick_search_container']
                ?? ($searchContainers['catalog_product_autocomplete'] ?? false));
            // @codingStandardsIgnoreEnd

            if ($applyToQueries) {
                $containerData = ['apply_to' => (int) true];
                $queryIds      = $this->resource->getQueryIdsByOptimizer($optimizer);
                if (!empty($queryIds)) {
                    $containerData['query_ids'] = $queryIds;
                }
                $data[$optimizer->getId()]['quick_search_container'] = $containerData;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
