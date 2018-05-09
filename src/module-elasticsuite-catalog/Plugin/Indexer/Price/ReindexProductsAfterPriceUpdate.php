<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Plugin\Indexer\Price;

use Smile\ElasticsuiteCatalog\Plugin\Indexer\AbstractIndexerPlugin;

/**
 * Price indexer operations related plugin.
 * Used to index products into ES after their price information are indexed by legacy Magento indexer.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ReindexProductsAfterPriceUpdate extends AbstractIndexerPlugin
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Process row indexation into ES after the precedent stock index
     *
     * @param \Magento\Catalog\Model\Indexer\Product\Price $subject    The Price indexer
     * @param \Closure                                     $proceed    The ::execute() function of $subject
     * @param int[]                                        $productIds The product ids being reindexed
     *
     * @return void
     */
    public function aroundExecute(
        \Magento\Catalog\Model\Indexer\Product\Price $subject,
        \Closure $proceed,
        $productIds
    ) {
        $proceed($productIds);

        $this->processFullTextIndex($productIds);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Process row indexation into ES after the precedent stock index
     *
     * @param \Magento\Catalog\Model\Indexer\Product\Price $subject   The Price indexer
     * @param \Closure                                     $proceed   The ::executeRow() function of $subject
     * @param int                                          $productId The product id being reindexed
     *
     * @return void
     */
    public function aroundExecuteRow(
        \Magento\Catalog\Model\Indexer\Product\Price $subject,
        \Closure $proceed,
        $productId
    ) {
        $proceed($productId);

        $this->processFullTextIndex($productId);

        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * Process list indexation into ES after the precedent stock index
     *
     * @param \Magento\Catalog\Model\Indexer\Product\Price $subject    The Price indexer
     * @param \Closure                                     $proceed    The ::execute() function of $subject
     * @param int[]                                        $productIds The product ids being reindexed
     *
     * @return void
     */
    public function aroundExecuteList(
        \Magento\Catalog\Model\Indexer\Product\Price $subject,
        \Closure $proceed,
        array $productIds
    ) {
        $proceed($productIds);

        $this->processFullTextIndex($productIds);

        return;
    }
}
