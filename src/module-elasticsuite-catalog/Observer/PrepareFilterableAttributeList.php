<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2017 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Observer;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute as SaveHandler;

/**
 * Observer called when preparing save on a category.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class PrepareFilterableAttributeList implements ObserverInterface
{
    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute
     */
    private $saveHandler;

    /**
     * PrepareFilterableAttributeList constructor.
     *
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Category\FilterableAttribute $saveHandler Save Handler
     */
    public function __construct(SaveHandler $saveHandler)
    {
        $this->saveHandler = $saveHandler;
    }

    /**
     * Prepare Category data when saved through admin form.
     *
     * @event catalog_category_prepare_save
     *
     * @param Observer $observer The observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var CategoryInterface $category */
        $category = $observer->getEvent()->getCategory();

        /** @var RequestInterface $request */
        $request = $observer->getEvent()->getRequest();

        if ($category) {
            $data      = $request->getParam('facet_config', []);
            $sortOrder = $request->getParam('facet_config_order', []);
            $position  = [];

            foreach ($data as $key => &$item) {
                $item['position'] = isset($sortOrder[$key]) ? $sortOrder[$key] : $item['position'];
                $position[] = (int) $item['position'];
            }
            array_multisort($position, SORT_ASC, $data);

            $category->getResource()->addCommitCallback(
                function () use ($category, $data) {
                    $this->saveHandler->saveAttributesData($category->getId(), $data);
                }
            );
        }
    }
}
