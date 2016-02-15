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
namespace Smile\ElasticSuiteTracker\Block\Variables\Page;
use Magento\Framework\View\Element\Template;

/**
 * Class Base
 *
 * @package   Smile\ElasticSuiteTracker\Block\Variables\Page
 * @copyright 2016 Smile
 */
class Cms extends \Smile\ElasticSuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Catalog layer
     *
     * @var \Magento\Cms\Model\Page
     */
    protected $_page;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                       $context       The template context
     * @param \Magento\Framework\Json\Helper\Data    $jsonHelper    The Magento's JSON Helper
     * @param \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper The Smile Tracker helper
     * @param \Magento\Framework\Registry            $registry      Magento Core Registry
     * @param \Magento\Cms\Model\Page                $page          The CMS Page
     * @param array                                  $data          The block data
     *
     * @return Cms
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Cms\Model\Page $page,
        array $data = []
    ) {
        $this->_page = $page;

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Append the CMS page viewed identifier and title to the list of tracked variables
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = array();

        if ($this->_page->getId()) {
            $variables['cms.identifier'] = $this->_page->getIdentifier();
            $variables['cms.title']      = $this->_page->getTitle();
        }

        return $variables;
    }
}