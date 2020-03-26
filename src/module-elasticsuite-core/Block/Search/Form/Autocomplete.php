<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Block\Search\Form;

use Magento\Framework\Locale\FormatInterface;

/**
 * Quick Form block for Autocomplete
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Autocomplete extends \Magento\Framework\View\Element\Template
{
    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * Mini constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context      Block context
     * @param \Magento\Framework\Json\Helper\Data              $jsonHelper   JSON helper
     * @param \Magento\Framework\Locale\FormatInterface        $localeFormat Locale Format
     * @param array                                            $data         The data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        FormatInterface $localeFormat,
        array $data
    ) {
        $this->jsonHelper   = $jsonHelper;
        $this->localeFormat = $localeFormat;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve templates renderers for autocomplete results
     *
     * @return array
     */
    public function getSuggestRenderers()
    {
        return $this->getData('rendererList');
    }

    /**
     * Retrieve Renderers used to draw the suggest in JSON format
     *
     * @return string
     */
    public function getJsonSuggestRenderers()
    {
        $templates = $this->getSuggestRenderers();

        return $this->jsonHelper->jsonEncode($templates);
    }

    /**
     * Retrieve price format configuration in Json.
     *
     * @return array
     */
    public function getJsonPriceFormat()
    {
        return $this->jsonHelper->jsonEncode($this->getPriceFormat());
    }

    /**
     * Retrieve price format configuration.
     *
     * @return array
     */
    protected function getPriceFormat()
    {
        return $this->localeFormat->getPriceFormat();
    }
}
