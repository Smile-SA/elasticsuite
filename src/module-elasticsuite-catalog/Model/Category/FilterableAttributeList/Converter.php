<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Model\Category\FilterableAttributeList;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

/**
 * Layered Navigation Filter converter.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Converter
{
    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    private $dataObjectFactory;

    /**
     * Converter constructor.
     *
     * @param ProductAttributeRepositoryInterface   $attributeRepositoryInterface Product Attribute Repository
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory            Data Object Factory
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepositoryInterface,
        \Magento\Framework\DataObject\Factory $dataObjectFactory
    ) {
        $this->attributeRepository = $attributeRepositoryInterface;
        $this->dataObjectFactory   = $dataObjectFactory;
    }

    /**
     * Convert a tuple between Category Id and array values (eg from DB) to a proper object.
     *
     * @param array $data Attributes Data
     *
     * @return mixed
     */
    public function toEntity(array $data)
    {
        $attributes = [];
        foreach ($data as $row) {
            $attribute = $this->attributeRepository->get((int) $row['attribute_id']);
            $attribute->addData($row);
            $attributes[] = $attribute;
        }

        return $attributes;
    }

    /**
     * Convert a layered navigation filters entity to raw data (eg. to store it in DB).
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface[] $filterableAttributes Filterable Attributes
     *
     * @return array
     */
    public function fromEntity($filterableAttributes)
    {
        $data      = [];
        $positions = [];
        foreach ($filterableAttributes as $attribute) {
            $data[] = [
                'attribute_id' => $attribute->getAttributeId(),
                'position'     => $attribute->getPosition(),
                'display_mode' => $attribute->getDisplayMode(),
            ];

            $positions[] = (int) $attribute->getPosition();
        }

        array_multisort($positions, SORT_ASC, $data);

        return $data;
    }
}
