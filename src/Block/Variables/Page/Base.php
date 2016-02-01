<?php
/**
 * _______________________________
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile________________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Apache License Version 2.0
 */
namespace Smile\Tracker\Block\Variables\Page;
use Magento\Framework\App\Cache\Type;
use Magento\Framework\View\Element\Template;

/**
 * Class Base
 *
 * @package   Smile\Tracker\Block\Variables\Page
 * @copyright 2016 Smile
 */
class Base extends \Smile\Tracker\Block\Variables\Page\AbstractBlock
{
    /**
     * @var \Magento\Framework\View\Layout\PageType\Config The page type configuration
     */
    protected $_pageTypeConfig;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                               $context        The template context
     * @param \Magento\Framework\Json\Helper\Data            $jsonHelper     The Magento's JSON Helper
     * @param \Smile\Tracker\Helper\Data                     $trackerHelper  The Smile_Tracker helper
     * @param \Magento\Framework\Registry                    $registry       Magento Core Registry
     * @param \Magento\Framework\View\Layout\PageType\Config $pageTypeConfig The page type configuration
     * @param array                                          $data           The block data
     *
     * @return Base
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\Tracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Layout\PageType\Config $pageTypeConfig,
        array $data = []
    ) {
        $this->_pageTypeConfig = $pageTypeConfig;
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
        return array(
            'type.identifier' => $this->getPageTypeIdentifier(),
            'type.label'      => stripslashes($this->getPageTypeLabel()),
        );
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
            $labelByIdentifier = $this->_getPageTypeLabelMap();

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
    protected function _getPageTypeLabelMap()
    {
        $labelByIdentifier = array();

        $pageTypes = $this->_pageTypeConfig->getPageTypes();
        foreach ($pageTypes as $identifier => $pageType) {
            $labelByIdentifier[$identifier] = $pageType['label'];
        }

        return $labelByIdentifier;
    }

}