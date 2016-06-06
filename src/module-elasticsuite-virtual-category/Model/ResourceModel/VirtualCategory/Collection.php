<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\ResourceModel\VirtualCategory;

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
     * @var \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
     */
    private $virtualAttributeBackend;

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * {@inheritDoc}
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            $this->getVirtualAttributeBackend()->afterLoad($item);
        }

        return $this;
    }

    /**
     * Virtual attribute backend.
     *
     * @return \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
     */
    private function getVirtualAttributeBackend()
    {
        if ($this->virtualAttributeBackend === null) {
            $this->virtualAttributeBackend = $this->getResource()->getAttribute('virtual_rule')->getBackend();
        }

        return $this->virtualAttributeBackend;
    }
}
