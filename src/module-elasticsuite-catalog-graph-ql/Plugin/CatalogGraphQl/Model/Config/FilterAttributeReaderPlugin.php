<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogGraphQl
 * @author    Pierre Gauthier <pigau@smile.fr>
 * @copyright 2023 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogGraphQl\Plugin\CatalogGraphQl\Model\Config;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\CatalogGraphQl\Model\Config\FilterAttributeReader;

/**
 * Plugin to handle decimal and int attributes filter types in GraphQL schema
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogGraphQl
 * @author   Pierre Gauthier <pigau@smile.fr>
 */
class FilterAttributeReaderPlugin
{
    /**
     * Filter input types
     */
    private const FILTER_RANGE_TYPE = 'FilterRangeTypeInput';
    private const FILTER_MATCH_TYPE = 'FilterMatchTypeInput';

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory Collection factory.
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * After plugin for read method to modify filter types for decimal and int attributes
     *
     * @param FilterAttributeReader $subject Plugin subject.
     * @param array                 $result  Result of the original method.
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRead(
        FilterAttributeReader $subject,
        array $result
    ): array {
        // Get filter attributes using the same logic as the original class.
        $filterAttributes = $this->getFilterAttributes();

        foreach ($result as &$typeConfig) {
            if (isset($typeConfig['fields'])) {
                foreach ($typeConfig['fields'] as $attributeCode => &$fieldConfig) {
                    if (isset($filterAttributes[$attributeCode])) {
                        $attribute = $filterAttributes[$attributeCode];
                        $backendType = $attribute->getBackendType();
                        if (self::FILTER_MATCH_TYPE === $fieldConfig['type']
                            && in_array($backendType, ['decimal', 'int'])
                            && !$attribute->usesSource()
                        ) {
                            $fieldConfig['type'] = self::FILTER_RANGE_TYPE;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get attributes to use in product filter input
     *
     * @return array
     */
    private function getFilterAttributes(): array
    {
        $filterableAttributes = $this->collectionFactory
            ->create()
            ->addHasOptionsFilter()
            ->addIsFilterableFilter()
            ->getItems();

        $searchableAttributes = $this->collectionFactory
            ->create()
            ->addHasOptionsFilter()
            ->addIsSearchableFilter()
            ->addDisplayInAdvancedSearchFilter()
            ->getItems();

        $result = [];

        // Index filterable & searchable attributes by attribute code.
        foreach ($filterableAttributes + $searchableAttributes as $attribute) {
            $result[$attribute->getAttributeCode()] = $attribute;
        }

        return $result;
    }
}
