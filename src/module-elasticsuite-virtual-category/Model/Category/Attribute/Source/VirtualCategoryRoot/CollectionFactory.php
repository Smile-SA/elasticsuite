<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\Source\VirtualCategoryRoot;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

/**
 * Custom Category Collection factory for Virtual Category Root field in Ui Components
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class CollectionFactory extends \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * CollectionFactory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager Object Manager
     * @param \Magento\Framework\Registry               $registry      Application Registry
     */
    public function __construct(ObjectManagerInterface $objectManager, Registry $registry)
    {
        parent::__construct($objectManager);
        $this->registry = $registry;
    }

    /**
     * Create collection
     *
     * @param array $data Collection data
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(array $data = [])
    {
        $collection = parent::create($data);

        if ($this->getCurrentCategory() && $this->getCurrentCategory()->getLevel() >= 2) {
            $collection->addAttributeToFilter('entity_id', ['neq' => (int) $this->getCurrentCategory()->getId()]);
        }

        return $collection;
    }

    /**
     * Get the currently edited category, if any.
     *
     * @return \Magento\Catalog\Model\Category
     */
    private function getCurrentCategory()
    {
        $category = false;

        if ($this->registry->registry('current_category') && $this->registry->registry('current_category')->getId()) {
            $category = $this->registry->registry('current_category');
        }

        return $category;
    }
}
