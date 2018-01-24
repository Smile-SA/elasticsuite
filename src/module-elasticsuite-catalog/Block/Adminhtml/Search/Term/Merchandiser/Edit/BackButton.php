<?php

namespace Smile\ElasticsuiteCatalog\Block\Adminhtml\Search\Term\Merchandiser\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     * @codeCoverageIgnore
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

    public function getQueryUrl()
    {
        return $this->getUrl('search/term/edit', ['id' => $this->getQueryId()]);
    }
}
