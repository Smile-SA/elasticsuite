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
use Magento\Framework\View\Element\Template;

/**
 * Class AbstractBlock
 *
 * @package   Smile\Tracker\Block\Variables\Page
 * @copyright 2016 Smile
 */
class AbstractBlock extends \Smile\Tracker\Block\Variables\AbstractBlock
{
    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                    $context       The template context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper    The Magento's JSON Helper
     * @param \Smile\Tracker\Helper\Data          $trackerHelper The Smile_Tracker helper
     * @param \Magento\Framework\Registry         $registry      Magento Core Registry
     * @param array                               $data          The block data
     *
     * @return AbstractBlock
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\Tracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $data['template'] = 'Smile_Tracker::/variables/page.phtml';
        return parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }
}