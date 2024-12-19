<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2024 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Controller\Adminhtml\Healthcheck;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;

/**
 * Class Index.
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
        $breadMain = __('Healthcheck');

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend($breadMain);

        return $resultPage;
    }
}
