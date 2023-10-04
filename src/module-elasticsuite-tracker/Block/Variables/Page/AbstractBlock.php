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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteTracker\Block\Variables\Page;

use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Smile\ElasticsuiteTracker\Helper\Data as TrackerHelper;

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
     * @param Template\Context $context       The template context
     * @param Data             $jsonHelper    The Magento's JSON Helper
     * @param TrackerHelper    $trackerHelper The Smile Tracker helper
     * @param Registry         $registry      Magento Core Registry
     * @param array            $data          The block data
     */
    public function __construct(
        Template\Context $context,
        Data $jsonHelper,
        TrackerHelper $trackerHelper,
        Registry $registry,
        array $data = []
    ) {
        $data['template'] = 'Smile_ElasticsuiteTracker::/variables/page.phtml';

        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }
}
