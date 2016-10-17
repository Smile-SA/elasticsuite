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
namespace Smile\ElasticsuiteCatalog\Block\Navigation\Renderer;

use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\View\Element\Template;

/**
 * Abstract facet renderer block.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
abstract class AbstractRenderer extends Template implements FilterRendererInterface
{
    /**
     * @var FilterInterface
     */
    private $filter;

    /**
     * {@inheritDoc}
     */
    public function render(FilterInterface $filter)
    {
        $html         = '';
        $this->filter = $filter;

        if ($this->canRenderFilter()) {
            $this->assign('filterItems', $filter->getItems());
            $html = $this->_toHtml();
            $this->assign('filterItems', []);
        }

        return $html;
    }

    /**
     * @return FilterInterface
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Check if the current block can render a filter (previously set through ::setFilter).
     *
     * @return boolean
     */
    abstract protected function canRenderFilter();
}
