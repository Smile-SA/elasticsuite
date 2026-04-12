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

namespace Smile\ElasticsuiteCatalog\Model\Layer;

use Magento\Catalog\Model\Config\LayerCategoryConfig;
use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\ObjectManagerInterface;
use Smile\ElasticsuiteCatalog\Api\Layer\Filter\TypeProviderInterface;

/**
 * FilterList customization to support decimal filters.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterList extends \Magento\Catalog\Model\Layer\FilterList
{
    /**
     * Boolean filter name
     */
    const BOOLEAN_FILTER = 'boolean';

    /**
     * @var TypeProviderInterface[]
     */
    private $filterTypeProviders;

    /**
     * Constructor.
     *
     * @param ObjectManagerInterface           $objectManager        Object manager.
     * @param FilterableAttributeListInterface $filterableAttributes Filterable attributes list.
     * @param LayerCategoryConfig              $layerCategoryConfig  Category layer config.
     * @param array                            $filters              Core filters array.
     * @param array                            $filterTypeProviders  Injected custom type providers.
     */
    public function __construct(
        ObjectManagerInterface           $objectManager,
        FilterableAttributeListInterface $filterableAttributes,
        LayerCategoryConfig              $layerCategoryConfig,
        array                            $filters = [],
        array                            $filterTypeProviders = []
    ) {
        parent::__construct(
            $objectManager,
            $filterableAttributes,
            $layerCategoryConfig,
            $filters
        );

        $this->filterTypeProviders = $filterTypeProviders;
    }

    /**
     * {@inheritDoc}
     */
    protected function getAttributeFilterClass(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute)
    {
        $filterClassName = parent::getAttributeFilterClass($attribute);

        if ($attribute->getBackendType() == 'varchar' && $attribute->getFrontendClass() == 'validate-number') {
            $filterClassName = $this->filterTypes[self::DECIMAL_FILTER];
        }

        if (($attribute->getFrontendInput() == 'boolean')
            && ($attribute->getSourceModel() == 'Magento\Eav\Model\Entity\Attribute\Source\Boolean')
            && isset($this->filterTypes[self::BOOLEAN_FILTER])) {
            $filterClassName = $this->filterTypes[self::BOOLEAN_FILTER];
        }

        // Allow injected providers to override the filter class.
        foreach ($this->filterTypeProviders as $provider) {
            if ($provider instanceof TypeProviderInterface) {
                $filterClassName = $provider->getFilterClassName($attribute, $filterClassName);
            }
        }

        return $filterClassName;
    }
}
