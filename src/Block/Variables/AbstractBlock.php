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
namespace Smile\ElasticSuiteTracker\Block\Variables;

/**
 * Class AbstractBlock
 *
 * @package   Smile\ElasticSuiteTracker\Block\Variables
 * @copyright 2016 Smile
 */
class AbstractBlock extends \Magento\Framework\View\Element\Template
{
    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    /**
     * Generic tracker helper
     *
     * @var \Smile\Tracker\Helper\Data
     */
    protected $_trackerHelper;

    /**
     * Magento Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * PHP Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context       App context
     * @param \Magento\Framework\Json\Helper\Data              $jsonHelper    The Magento's JSON Helper
     * @param \Smile\ElasticSuiteTracker\Helper\Data           $trackerHelper The Smile Tracker helper
     * @param \Magento\Framework\Registry                      $registry      The Magento registry
     * @param array                                            $data          additional datas
     *
     * @return AbstractBlock
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticSuiteTracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_jsonHelper    = $jsonHelper;
        $this->_trackerHelper = $trackerHelper;
        $this->_registry      = $registry;
    }


    /**
     * Retrieve the Json Helper
     *
     * @return \Magento\Framework\Json\Helper\Data
     */
    public function getJsonHelper()
    {
        return $this->_jsonHelper;
    }

    /**
     * Retrieve the string escaper
     *
     * @return \Magento\Framework\Escaper
     */
    public function getEscaper()
    {
        return $this->_escaper;
    }
}