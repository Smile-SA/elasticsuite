<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Block\Adminhtml\Search\Term\Merchandiser\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Form back button.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->canRender('reset')) {
            $data = [
                'label' => __('Back'),
                'class' => 'back',
                'on_click' => "setLocation('" . $this->getQueryUrl() . "');",
                'sort_order' => 10,
            ];
        }

        return $data;
    }

    /**
     * Get back URL.
     *
     * @return string
     */
    public function getQueryUrl()
    {
        return $this->getUrl('search/term/edit', ['id' => $this->getQueryId()]);
    }
}
