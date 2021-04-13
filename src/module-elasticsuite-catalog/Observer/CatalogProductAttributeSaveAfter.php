<?php

namespace Smile\ElasticsuiteCatalog\Observer;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductAttributeSaveAfter implements ObserverInterface
{
    /**
     * Attribute field list that will invalidate cache and fulltext index on change
     *
     * @var array
     */
    private $attributeFields = [
        'is_searchable',
        'is_filterable',
        'is_filterable_in_search',
        'is_used_for_promo_rules',
        'used_for_sort_by'
    ];

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var Processor
     */
    private $fulltextIndexerProcessor;

    /**
     * CatalogProductAttributeSaveAfter constructor.
     *
     * @param TypeListInterface $cacheTypeList
     * @param Processor $fulltextIndexerProcessor
     */
    public function __construct(
        TypeListInterface $cacheTypeList,
        Processor $fulltextIndexerProcessor
    ) {
        $this->cacheTypeList = $cacheTypeList;
        $this->fulltextIndexerProcessor = $fulltextIndexerProcessor;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var  \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute */
        $attribute = $observer->getEvent()->getAttribute();

        if ($attribute->isObjectNew()) {
            return;
        }

        $shouldInvalidate = false;
        foreach ($this->attributeFields as $field) {
            if ($attribute->dataHasChangedFor($field)) {
                $shouldInvalidate = true;
                break;
            }
        }

        if ($shouldInvalidate) {
            $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
            $this->fulltextIndexerProcessor->markIndexerAsInvalid();
        }
    }
}
