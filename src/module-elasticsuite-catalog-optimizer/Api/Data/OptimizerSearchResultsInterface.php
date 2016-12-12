<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Api\Data;

use \Magento\Framework\Api\SearchResultsInterface;

/**
 * Search Result Interface for Optimizer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
interface OptimizerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Optimizers list.
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface[]
     */
    public function getItems();

    /**
     * Set Optimizers list.
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface[] $items list of optimizers
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerSearchResultsInterface
     */
    public function setItems(array $items);
}
