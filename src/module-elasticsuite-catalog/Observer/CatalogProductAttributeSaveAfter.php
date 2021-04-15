<?php

namespace Smile\ElasticsuiteCatalog\Observer;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CatalogProductAttributeSaveAfter implements ObserverInterface
{
    /**
     * Attribute field list that will invalidate "Configuration" cache on change
     *
     * @var array
     */
    private $attributeFields = [
        'is_filterable',
        'is_filterable_in_search',
        'is_searchable',
        'is_used_for_promo_rules',
        'search_weight',
        'used_for_sort_by',
    ];

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * CatalogProductAttributeSaveAfter constructor.
     *
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        TypeListInterface $cacheTypeList
    ) {
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var  \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute */
        $attribute = $observer->getEvent()->getAttribute();

        $shouldInvalidate = false;
        foreach ($this->attributeFields as $field) {
            if ($attribute->dataHasChangedFor($field)) {
                $shouldInvalidate = true;
                break;
            }
        }

        if ($shouldInvalidate) {
            $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        }
    }
}
