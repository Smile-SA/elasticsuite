<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteVirtualCategory
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteVirtualCategory\Observer;

/**
 * Observer that convert 'virtual_rule' attribute to string value to ensure it gets properly processed by the
 * UpdateAttributes operation of the Entity Manager.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteVirtualCategory
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ConvertVirtualRuleOnSave implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\SaveHandler
     */
    private $saveHandler;

    /**
     * FlatPlugin constructor.
     *
     * @param \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\SaveHandler $saveHandler Read Handler
     */
    public function __construct(
        \Smile\ElasticsuiteVirtualCategory\Model\Category\Attribute\VirtualRule\SaveHandler $saveHandler
    ) {
        $this->saveHandler = $saveHandler;
    }

    /**
     * Observer listening to magento_catalog_api_data_categoryinterface_save_before
     *
     * @param \Magento\Framework\Event\Observer $observer The Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $entity = $observer->getEntity();

        if ($entity->getVirtualRule() !== null) {
            $this->saveHandler->execute($entity);
        }
    }
}
