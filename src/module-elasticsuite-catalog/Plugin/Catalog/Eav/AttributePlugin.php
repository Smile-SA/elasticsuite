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
    private $dataHasChangedForFields = [
        'is_filterable',
        'is_filterable_in_search',
        'is_searchable',
        'is_used_for_promo_rules',
        'search_weight',
        'used_for_sort_by',
    ];

    /**
     * @var \Smile\ElasticsuiteCore\Index\Indices\Config
     */
    private $indicesConfig;

    /**
     * AttributePlugin constructor.
     *
     * @param \Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig Indices config.
     */
    public function __construct(\Smile\ElasticsuiteCore\Index\Indices\Config $indicesConfig)
    {
        $this->indicesConfig = $indicesConfig;
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
        $cleanCache = false;

        foreach ($this->dataHasChangedForFields as $field) {
            if ($subject->dataHasChangedFor($field)) {
                $cleanCache = true;
                break;
            }
        }

        if ($cleanCache) {
            $this->indicesConfig->reset();
        }

        return $result;
    }
}
