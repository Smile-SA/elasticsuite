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

use Magento\Framework\View\Element\Template;

/**
 * Abstract block for page tracking, inherited by all other page tracking blocks
 *
 * @category Smile
 * @package  Smile\ElasticsuiteTracker
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class AbstractBlock extends \Smile\ElasticsuiteTracker\Block\Variables\AbstractBlock
{
    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                       $context       The template context
     * @param \Magento\Framework\Json\Helper\Data    $jsonHelper    The Magento's JSON Helper
     * @param \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper The Smile Tracker helper
     * @param \Magento\Framework\Registry            $registry      Magento Core Registry
     * @param array                                  $data          The block data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $data['template'] = 'Smile_ElasticsuiteTracker::/variables/page.phtml';

        return parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }
}
