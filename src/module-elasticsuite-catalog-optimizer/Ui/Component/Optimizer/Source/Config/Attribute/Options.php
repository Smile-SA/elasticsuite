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
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Source\Config\Attribute;

use \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Config\Attributes\CollectionFactory as AttributesCollectionFactory;

/**
 * Attributes Options Values for linear-based optimizers.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Options implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Config\Attributes\CollectionFactory
     */
    private $attributesCollectionFactory;

    /**
     * @var array|null
     */
    private $attributesList;

    /**
     * Options constructor.
     *
     * @param AttributesCollectionFactory $attributesCollectionFactory Attributes Collection
     */
    public function __construct(AttributesCollectionFactory $attributesCollectionFactory)
    {
        $this->attributesCollectionFactory = $attributesCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getAttributesList();
    }

    /**
     * Retrieve list of attributes that can be used to define virtual attributes rules.
     *
     * @return array
     */
    private function getAttributesList()
    {
        if (null === $this->attributesList) {
            $this->attributesList = [];

            $collection = $this->attributesCollectionFactory->create();

            foreach ($collection as $attribute) {
                $this->attributesList[$attribute->getId()] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getFrontendLabel(),
                ];
            }
        }

        return $this->attributesList;
    }
}
