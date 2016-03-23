<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Plugin\Indexer\Stock;

use Smile\ElasticSuiteCatalog\Plugin\Indexer\AbstractIndexerPlugin;

/**
 * Stock (CatalogInventory) indexer operations related plugin.
 * Used to index products into ES after their stock information are indexed by legacy Magento CatalogInventory indexer.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReindexProductsAfterStockUpdate extends AbstractIndexerPlugin
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Process row indexation into ES after the precedent stock index
     *
     * @param \Magento\CatalogInventory\Model\Indexer\Stock $subject    The CatalogInventory indexer
     * @param \Closure                                      $proceed    The ::execute() function of $subject
     * @param int[]                                         $productIds The product ids being reindexed
     *
     * @return void
     */
    public function aroundExecute(
        \Magento\CatalogInventory\Model\Indexer\Stock $subject,
        \Closure $proceed,
        $productIds
    ) {
        $proceed($productIds);

        $this->processFullTextIndex($productIds);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Process row indexation into ES after the precedent stock index
     *
     * @param \Magento\CatalogInventory\Model\Indexer\Stock $subject   The CatalogInventory indexer
     * @param \Closure                                      $proceed   The ::executeRow() function of $subject
     * @param int                                           $productId The product id being reindexed
     *
     * @return void
     */
    public function aroundExecuteRow(
        \Magento\CatalogInventory\Model\Indexer\Stock $subject,
        \Closure $proceed,
        $productId
    ) {
        $proceed($productId);

        $this->processFullTextIndex($productId);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * Process list indexation into ES after the precedent stock index
     *
     * @param \Magento\CatalogInventory\Model\Indexer\Stock $subject    The CatalogInventory indexer
     * @param \Closure                                      $proceed    The ::execute() function of $subject
     * @param int[]                                         $productIds The product ids being reindexed
     *
     * @return void
     */
    public function aroundExecuteList(
        \Magento\CatalogInventory\Model\Indexer\Stock $subject,
        \Closure $proceed,
        array $productIds
    ) {
        $proceed($productIds);

        $this->processFullTextIndex($productIds);
    }
}
