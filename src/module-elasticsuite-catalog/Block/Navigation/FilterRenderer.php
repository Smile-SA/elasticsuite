<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block\Navigation;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\LayeredNavigation\Block\Navigation\FilterRendererInterface;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;

/**
 * This block handle the facet rendering by choosing one or this child block or fallback to the default templates.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class FilterRenderer extends AbstractBlock implements FilterRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(FilterInterface $filter)
    {
        $this->setFilter($filter);

        return $this->_toHtml();
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    public function _toHtml()
    {
        $html = '';

        foreach ($this->getChildNames() as $childName) {
            if (trim((string) $html) === '') {
                $renderer = $this->getChildBlock($childName);
                $html = $renderer->render($this->getFilter());
            }
        }

        return $html;
    }
}
