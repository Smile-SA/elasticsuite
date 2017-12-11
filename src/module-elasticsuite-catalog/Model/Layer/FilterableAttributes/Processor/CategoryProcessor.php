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
namespace Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\Processor;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCatalog\Model\Layer\Category\FilterableAttributeListFactory;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\Source\DisplayMode;
use Smile\ElasticsuiteCatalog\Model\Layer\FilterableAttributes\ProcessorInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory as CollectionFactory;

/**
 * Category Processor for Filterable Attributes
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CategoryProcessor implements ProcessorInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute\CollectionFactory
     */
    private $collectionFactory;

    /**
     * CategoryProvider constructor.
     *
     * @param CollectionFactory $collectionFactory Collection factory.
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function processFilterableAttributes(
        FilterableAttributeListInterface $filterableAttributeList,
        \Magento\Catalog\Model\Layer $layer
    ) {
        $attributeList = $filterableAttributeList->getList();

        if ($layer->getCurrentCategory()) {
            $categoryFilterableAttributes = $this->collectionFactory->create(['category' => $layer->getCurrentCategory()]);
            $categoryFilterableAttributes
                ->setItemObjectClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)
                ->setOrder('position', 'ASC')
                ->addSetInfo(true)
                ->addIsFilterableFilter();

            $positions = [];
            foreach ($attributeList as $key => $attribute) {
                $categoryAttribute = $categoryFilterableAttributes->getItemByColumnValue('attribute_id', $attribute->getAttributeId());

                // Remove not found attributes or always hidden ones.
                if (null === $categoryAttribute || (int) $categoryAttribute->getDisplayMode() === DisplayMode::ALWAYS_HIDDEN) {
                    $attributeList->removeItemByKey($key);
                    continue;
                }

                // Set Always displayed attributes to a min coverage of 0 to enforce their display.
                if ((int) $categoryAttribute->getDisplayMode() === DisplayMode::ALWAYS_DISPLAYED) {
                    $attribute->setFacetMinCoverageRate(0);
                }

                $positions[] = (int) $categoryAttribute->getPosition();
            }

            $attributeList = $attributeList->getItems();
            array_multisort($positions, SORT_ASC, $attributeList);
        }

        return $attributeList;
    }
}
