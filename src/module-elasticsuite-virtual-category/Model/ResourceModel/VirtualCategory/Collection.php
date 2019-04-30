<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory;

use \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler as VirtualRuleReadHandler;

/**
 * Category collection with automatic loading of the virtual category using the attribute backend.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Category\Collection
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler as VirtualRuleReadHandler
     */
    private $virtualRuleReadHandler;

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            $this->getVirtualRuleReadHandler()->execute($item);
        }

        return $this;
    }

    /**
     * Virtual rule read handler.
     *
     * @return \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\ReadHandler
     */
    private function getVirtualRuleReadHandler()
    {
        if ($this->virtualRuleReadHandler === null) {
            $this->virtualRuleReadHandler = $this->_universalFactory->create(VirtualRuleReadHandler::class);
        }

        return $this->virtualRuleReadHandler;
    }
}
