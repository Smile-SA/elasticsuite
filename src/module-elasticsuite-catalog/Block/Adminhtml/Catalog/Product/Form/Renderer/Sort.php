<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCatalog\Block\Adminhtml\Catalog\Product\Form\Renderer;

use Magento\Backend\Block\Template;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * A generic block to allow admin having a nice product sorter with preview and drag and drop feature.
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Sort extends Template implements RendererInterface
{
    /**
     * @var string
     */
    const JS_COMPONENT = 'Smile_ElasticSuiteCatalog/js/catalog/product/form/renderer/sort';

    /**
     * @var string
     */
    const JS_TEMPLATE  = 'Smile_ElasticSuiteCatalog/catalog/product/form/renderer/sort';

    /**
     * @var string
     */
    protected $_template = 'catalog/product/form/renderer/sort.phtml';

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    private $localeFormat;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Template\Context   $context      Template context.
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat Locale format.
     * @param array                                     $data         Additional data.
     */
    public function __construct(\Magento\Backend\Block\Template\Context $context, FormatInterface $localeFormat, array $data = [])
    {
        parent::__construct($context, $data);
        $this->localeFormat = $localeFormat;
    }

    /**
     * {@inheritDoc}
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * {@inheritDoc}
     */
    public function getJsLayout()
    {
        $layoutJsComponents = [];
        $layoutJsComponents['adminProductSort']['component'] = self::JS_COMPONENT;
        $layoutJsComponents['adminProductSort']['config'] = [
            'template'          => self::JS_TEMPLATE,
            'loadUrl'           => $this->getElement()->getLoadUrl(),
            'targetElementName' => $this->getElement()->getName(),
            'formId'            => $this->getElement()->getFormId(),
            'refreshElements'   => $this->getElement()->getRefreshElements(),
            'savedPositions'    => $this->getElement()->getSavedPositions(),
            'pageSize'          => $this->getElement()->getPageSize(),
            'priceFormat'       => $this->localeFormat->getPriceFormat(),
        ];

        return json_encode(['components' => $layoutJsComponents]);
    }
}
