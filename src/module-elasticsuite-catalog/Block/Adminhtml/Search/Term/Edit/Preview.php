<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalog\Block\Adminhtml\Search\Term\Edit;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * Add the merchandiser button in the search term form.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Preview extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var \Magento\Backend\Block\Widget\Button\ButtonList
     */
    private $buttonList;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\Block\Widget\Context $context    Block context.
     * @param \Magento\Backend\Model\UrlInterface   $urlBuilder URL Builder.
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->buttonList = $context->getButtonList();
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * {@inheritDoc}
     */
    protected function _construct()
    {
        parent::_construct();

        $this->buttonList->add(
            'merchandiser-button',
            [
                'label' => __('Merchandiser'),
                'class' => 'delete',
                'onclick' => "setLocation('" . $this->getMerchandiserUrl() . "')",
            ]
        );
    }

    /**
     * Retrieve merchandiser button URL.
     * @return string
     */
    private function getMerchandiserUrl()
    {
        $queryId = $this->getRequest()->getParam('id');

        return $this->urlBuilder->getUrl('search/term_merchandiser/edit', ['id' => $queryId]);
    }
}
