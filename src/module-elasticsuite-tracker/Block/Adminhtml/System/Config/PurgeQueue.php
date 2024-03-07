<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Richard BAYET <richard.bayet@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types = 1);

namespace Smile\ElasticsuiteTracker\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Purge queue system config button field.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Richard Bayet <richard.bayet@smile.fr>
 */
class PurgeQueue extends Field
{
    /** @var Json */
    private $json;

    /**
     * Constructor.
     *
     * @param Json                    $json           Json helper.
     * @param Context                 $context        Context.
     * @param array                   $data           Data.
     * @param SecureHtmlRenderer|null $secureRenderer Secure renderer.
     */
    public function __construct(
        Json $json,
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
        $this->json = $json;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element Form element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element = clone $element;
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param AbstractElement $element Form element
     *
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $button = $this->getLayout()->createBlock(
            Button::class,
            'ELASTICSUITE_INVALID_TRACKER_EVENTS_PURGE_BUTTON',
            [
                'data' => [
                    'type' => 'button',
                    'label' => __('Purge now'),
                    'title' => __('Purge the queue of all invalid events now'),
                ],
            ]
        );
        $button->addData([
            'data_attribute' => [
                'mage-init' => $this->getDataMageInitProperty($button->getHtmlId()),
            ],
        ]);

        return $button->toHtml();
    }

    /**
     * Return the data-mage-init property.
     *
     * @param string $buttonId Purge event queue button id.
     *
     * @return string
     */
    private function getDataMageInitProperty($buttonId): string
    {
        return $this->json->serialize([
            'purgeEventQueue' => [
                'url' => $this->_escaper->escapeUrl(
                    $this->_urlBuilder->getUrl('elasticsuite/tracker/purgeQueue')
                ),
                'elementId' => $buttonId,
                'successText' => __('Queue successfully purged'),
                'failedText' => __('Failure to purge the queue'),
            ],
            'validation' => [],
        ]);
    }
}
