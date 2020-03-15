<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * ElasticSuite Indices Adminhtml Index controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
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
        $breadMain = __('Indices Status');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend($breadMain);

        return $resultPage;
    }
}
