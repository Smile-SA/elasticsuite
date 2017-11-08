<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Model\Layer;

use Smile\ElasticsuiteCatalog\Model\Category\FilterableAttribute\Source\DisplayMode;

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
     * {@inheritdoc}
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        if (!count($this->filters)) {
            $this->filters = [
                $this->objectManager->create($this->filterTypes[self::CATEGORY_FILTER], ['layer' => $layer]),
            ];

            $filterableAttributes = $layer->getCurrentCategory()->getExtensionAttributes()->getFilterableAttributeList();
            if (empty($filterableAttributes)) {
                $filterableAttributes = $this->filterableAttributes->getList($layer->getCurrentCategory());
            }

            foreach ($filterableAttributes as $attribute) {
                if ($attribute->hasDisplayMode()) {
                    // Do not create a filter for always-hidden attributes.
                    if ((int) $attribute->getDisplayMode() === DisplayMode::ALWAYS_HIDDEN) {
                        continue;
                    }
                    // Set Coverage to 0 for always-displayed attributes.
                    if ((int) $attribute->getDisplayMode() === DisplayMode::ALWAYS_DISPLAYED) {
                        $attribute->setFacetMinCoverageRate(0);
                    }
                }

                $this->filters[] = $this->createAttributeFilter($attribute, $layer);
            }
        }

        return $this->filters;
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

        return $filterClassName;
    }
}
