<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Framework\App\Cache\Type;
use Magento\Framework\View\Element\Template;

/**
 * Base variables block for page tracking, exposes all base tracking variables
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Base extends \Smile\ElasticsuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * @var \Magento\Framework\View\Layout\PageType\Config The page type configuration
     */
    private $pageTypeConfig;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                               $context        The template context
     * @param \Magento\Framework\Json\Helper\Data            $jsonHelper     The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data         $trackerHelper  The Smile Tracker helper
     * @param \Magento\Framework\Registry                    $registry       Magento Core Registry
     * @param \Magento\Framework\View\Layout\PageType\Config $pageTypeConfig The page type configuration
     * @param array                                          $data           The block data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Layout\PageType\Config $pageTypeConfig,
        array $data = []
    ) {
        $this->pageTypeConfig = $pageTypeConfig;

        return parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Append the page type data to the tracked variables list
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->getPageTypeInformations();
    }

    /**
     * List of the page type data
     *
     * @return array
     */
    public function getPageTypeInformations()
    {
        return [
            'type.identifier' => $this->getPageTypeIdentifier(),
            'type.label'      => stripslashes($this->getPageTypeLabel()),
        ];
    }

    /**
     * Page type identifier built from route (ex: catalog/product/view => catalog_product_view)
     *
     * @return string
     */
    public function getPageTypeIdentifier()
    {
        $request = $this->getRequest();

        return $request->getModuleName() . '_' . $request->getControllerName() . '_' . $request->getActionName();
    }

    /**
     * Human readable version of the page
     *
     * @return string
     */
    public function getPageTypeLabel()
    {
        if (!$this->getData('page_type_label')) {
            $label             = '';
            $identifier        = $this->getPageTypeIdentifier();
            $labelByIdentifier = $this->getPageTypeLabelMap();

            if (isset($labelByIdentifier[$identifier])) {
                $label = $labelByIdentifier[$identifier];
            }

            $this->setData('page_type_label', $label);
        }

        return $this->getData('page_type_label');
    }

    /**
     * Return the array of page labels from layout indexed by handle names.
     *
     * @return array
     */
    private function getPageTypeLabelMap()
    {
        $labelByIdentifier = [];

        $pageTypes = $this->pageTypeConfig->getPageTypes();
        foreach ($pageTypes as $identifier => $pageType) {
            $labelByIdentifier[$identifier] = $pageType['label'];
        }

        return $labelByIdentifier;
    }
}
