<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Model\Category\Indexer;

use Smile\ElasticSuiteCatalog\Model\Eav\Indexer\IndexerHandler as AbstractIndexer;

/**
 * Indexing operation handling for ElasticSearch engine.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class IndexerHandler extends AbstractIndexer
{
    const INDEX_NAME = 'catalog_category';
    const TYPE_NAME  = 'category';

    /**
     * {@inheritDoc}
     */
    public function __construct(
        \Smile\ElasticSuiteCore\Api\Index\IndexOperationInterface $indexOperation,
        \Magento\Framework\Indexer\SaveHandler\Batch $batch,
        $indexName = self::INDEX_NAME,
        $typeName = self::TYPE_NAME
    ) {
        parent::__construct($indexOperation, $batch, $indexName, $typeName);
    }
}
