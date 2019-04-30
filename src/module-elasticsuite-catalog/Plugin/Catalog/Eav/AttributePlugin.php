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
 * @copyright 2019 Smile
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

        if ($subject->dataHasChangedFor('search_weight')) {
            $cleanCache = true;
        }

        if ($cleanCache) {
            $this->indicesConfig->reset();
        }

        return $result;
    }
}
