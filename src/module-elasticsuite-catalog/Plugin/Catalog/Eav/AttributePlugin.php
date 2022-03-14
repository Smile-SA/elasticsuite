<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Plugin\Catalog\Eav;

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Catalog EAV Attribute plugin.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AttributePlugin
{
    /**
     * @var string[]
     */
    private $cleanCacheFields = [
        EavAttributeInterface::IS_FILTERABLE,
        EavAttributeInterface::IS_FILTERABLE_IN_SEARCH,
        EavAttributeInterface::IS_SEARCHABLE,
        EavAttributeInterface::IS_USED_FOR_PROMO_RULES,
        EavAttributeInterface::USED_FOR_SORT_BY,
        EavAttributeInterface::IS_VISIBLE_IN_ADVANCED_SEARCH,
        'search_weight',
    ];

    /**
     * @var \Smile\ElasticsuiteCore\Index\Indices\Config
     */
    private $indicesConfig;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * AttributePlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig   Indices config.
     * @param IndexerRegistry                              $indexerRegistry Indexer registry.
     */
    public function __construct(
        \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig,
        IndexerRegistry $indexerRegistry
    ) {
        $this->indicesConfig   = $indicesConfig;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Invalidate indices config after attribute is modified.
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $subject The attribute being saved
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $result  The attribute being saved
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function afterSave(
        \Magento\Catalog\Api\Data\ProductAttributeInterface $subject,
        \Magento\Catalog\Api\Data\ProductAttributeInterface $result
    ) {
        $cleanCache      = false;
        $invalidateIndex = false;

        foreach ($this->cleanCacheFields as $field) {
            if ($subject->dataHasChangedFor($field)) {
                $cleanCache = true;
                break;
            }
        }

        if ($subject->dataHasChangedFor(EavAttributeInterface::USED_FOR_SORT_BY)) {
            $invalidateIndex = true;
        }

        if ($subject->dataHasChangedFor('include_zero_false_values')) {
            $invalidateIndex = true;
        }

        if ($cleanCache) {
            $this->indicesConfig->reset();
        }

        if ($invalidateIndex) {
            $this->indexerRegistry->get(Fulltext::INDEXER_ID)->invalidate();
        }

        return $result;
    }
}
