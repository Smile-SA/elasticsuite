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
 * Class Base
 *
 * @package   Smile\Tracker\Block\Variables\Page
 * @copyright 2016 Smile
 */
class Search extends \Smile\Tracker\Block\Variables\Page\AbstractBlock
{
    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * Catalog search data
     *
     * @var \Magento\CatalogSearch\Helper\Data
     */
    protected $_catalogSearchData;

    /**
     * Set the default template for page variable blocks
     *
     * @param Template\Context                      $context           The template context
     * @param \Magento\Framework\Json\Helper\Data   $jsonHelper        The Magento's JSON Helper
     * @param \Smile\Tracker\Helper\Data            $trackerHelper     The Smile_Tracker helper
     * @param \Magento\Framework\Registry           $registry          Magento Core Registry
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver     The Magento layer resolver
     * @param \Magento\CatalogSearch\Helper\Data    $catalogSearchData The Catalogsearch data
     * @param array                                 $data              The block data
     *
     * @return Search
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\Tracker\Helper\Data $trackerHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\CatalogSearch\Helper\Data $catalogSearchData,
        array $data = []
    ) {
        $this->_catalogLayer      = $layerResolver->get();
        $this->_catalogSearchData = $catalogSearchData;
        parent::__construct($context, $jsonHelper, $trackerHelper, $registry, $data);
    }

    /**
     * Append the user fulltext query to the tracked variables list
     *
     * @return array
     */
    public function getVariables()
    {
        $variables = array(
            'search.query' => $this->_catalogSearchData->getEscapedQueryText()
        );

        // @TODO The isSpellchecked() method does not exists on native M2
        if ($layer = $this->_catalogLayer) {
            $productCollection = $layer->getProductCollection();
            $variables['search.is_spellchecked'] = (bool) false /*$productCollection->isSpellchecked()*/;
        }

        return $variables;
    }
}