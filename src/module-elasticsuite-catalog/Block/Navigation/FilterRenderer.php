<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block\Navigation;

use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;

/**
 * This block handle the facet rendering by choosing one or this child block or fallback to the default templates.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterRenderer extends Template implements FilterRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(FilterInterface $filter)
    {
        $this->setFilter($filter);
        $this->assign('filterItems', $filter->getItems());
        $html = $this->_toHtml();
        $this->assign('filterItems', []);

        return $html;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    public function _toHtml()
    {
        $html = false;

        foreach ($this->getChildNames() as $childName) {
            if ($html === false) {
                $renderer = $this->getChildBlock($childName);
                $html = $renderer->render($this->getFilter());
            }
        }

        if ($html === false) {
            $html = parent::_toHtml();
        }

        return $html;
    }
}
