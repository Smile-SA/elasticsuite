<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Searchandising Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteTracker
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteTracker\Block\Variables\Page;

use Magento\Framework\View\Element\Template;

/**
 * CMS Pages variables block for page tracking, exposes all CMS pages tracking variables
 *
 * @category Smile
 * @package  Smile_ElasticSuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Cms extends \Smile\ElasticSuiteTracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Catalog layer
     *
     * @var \Magento\Cms\Model\Page
     */
    private $page;

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
        $this->page = $page;

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Append the CMS page viewed identifier and title to the list of tracked variables
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = [];

        if ($this->page->getId()) {
            $variables['cms.identifier'] = $this->page->getIdentifier();
            $variables['cms.title']      = $this->page->getTitle();
        }

        return $variables;
    }
}
