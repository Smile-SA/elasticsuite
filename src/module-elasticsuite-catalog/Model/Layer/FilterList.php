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

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

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
     * @var \Magento\Framework\Event\ManagerInterface|null
     */
    private $eventManager = null;

    /**
     * FilterList constructor.
     *
     * @param ObjectManagerInterface           $objectManager        The object Manager
     * @param FilterableAttributeListInterface $filterableAttributes The Filterable attributes list
     * @param ManagerInterface                 $eventManager         The Event Manager
     * @param array                            $filters              The filters
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        FilterableAttributeListInterface $filterableAttributes,
        ManagerInterface $eventManager,
        array $filters
    ) {
        $this->eventManager = $eventManager;
        parent::__construct($objectManager, $filterableAttributes, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(\Magento\Catalog\Model\Layer $layer)
    {
        parent::getFilters($layer);

        $eventData = new \Magento\Framework\DataObject();
        $eventData->setFilters($this->filters)->setFilterTypes($this->filterTypes);

        $this->eventManager->dispatch(
            'smile_elasticsuite_layer_filterlist_get',
            [
                'event_data' => $eventData,
                'layer'      => $layer,
            ]
        );

        $this->filters = $eventData->getFilters();

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
