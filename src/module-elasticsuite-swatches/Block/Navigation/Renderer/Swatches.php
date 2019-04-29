<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteSwatches
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteSwatches\Block\Navigation\Renderer;

use Magento\Framework\View\Element\Template\Context;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Smile\ElasticsuiteCatalog\Block\Navigation\Renderer\AbstractRenderer;
use \Magento\Catalog\Helper\Data as CatalogHelper;

/**
 * This block handle swatches slider rendering.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteSwatches
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Swatches extends AbstractRenderer
{
    /**
     * @var string
     */
    protected $block = 'Smile\ElasticsuiteSwatches\Block\Navigation\Renderer\Swatches\RenderLayered';

    /**
     * @var SwatchHelper
     */
    private $swatchHelper;

    /**
     * Constructor.
     *
     * @param Context       $context       Template context.
     * @param CatalogHelper $catalogHelper Catalog helper.
     * @param SwatchHelper  $swatchHelper  Swatch helper.
     * @param array         $data          Custom data.
     */
    public function __construct(Context $context, CatalogHelper $catalogHelper, SwatchHelper $swatchHelper, array $data = [])
    {
        parent::__construct($context, $catalogHelper, $data);
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * {@inheritDoc}
     */
    protected function canRenderFilter()
    {
        $canRenderFilter = false;
        try {
            $attribute = $this->getFilter()->getAttributeModel();
            $canRenderFilter = $this->swatchHelper->isSwatchAttribute($attribute);
        } catch (\Exception $e) {
            ;
        }

        return $canRenderFilter;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _toHtml()
    {
        $html = false;

        if ($this->canRenderFilter()) {
            $html = $this->getLayout()
                ->createBlock($this->block)
                ->setSwatchFilter($this->getFilter())
                ->toHtml();
        }

        return $html;
    }
}
