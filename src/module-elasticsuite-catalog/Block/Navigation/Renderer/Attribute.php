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
namespace Smile\ElasticsuiteCatalog\Block\Navigation\Renderer;

/**
 * Default renderer that can render all attributes.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Attribute extends AbstractRenderer
{
    const JS_COMPONENT = 'Smile_ElasticsuiteCatalog/js/attribute-filter';

    /**
     * Returns true if checkox have to be enabled.
     *
     * @return boolean
     */
    public function isMultipleSelectEnabled()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsLayout()
    {
        $filterItems    = $this->getFilter()->getItems();

        $jsLayoutConfig = [
            'component'    => self::JS_COMPONENT,
            'maxSize'      => (int) $this->getFilter()->getAttributeModel()->getFacetMaxSize(),
            'hasMoreItems' => (bool) $this->getFilter()->hasMoreItems(),
        ];

        foreach ($filterItems as $item) {
            $jsLayoutConfig['items'][] = [
                'url'        => $item->getUrl(),
                'label'      => $item->getLabel(),
                'count'      => $item->getCount(),
                'isSelected' => (bool) $item->getIsSelected(),
            ];
        }

        return json_encode($jsLayoutConfig);
    }

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        return true;
    }
}
