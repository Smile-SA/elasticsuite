<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym HONCHARUK <vahonc@smile.fr>
 * @copyright 2022 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Analysis;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * ElasticSuite Indices Adminhtml Analysis controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym HONCHARUK <vahonc@smile.fr>
 */
class Index extends AbstractAction implements HttpGetActionInterface
{
    /**
     * @inheritdoc
     *
     * @return Page
     */
    public function execute(): Page
    {
        $breadMain = __('Analysis');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend($breadMain);

        return $resultPage;
    }
}
