<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteIndices
 * @author    Vadym Honcharuk <vahonc@smile.fr>
 * @copyright 2025 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteIndices\Controller\Adminhtml\Index;

use Exception;
use Magento\Backend\App\Action\Context;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Smile\ElasticsuiteIndices\Model\GhostIndexPurger;

/**
 * Controller for removing ghost indices.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Vadym Honcharuk <vahonc@smile.fr>
 */
class RemoveGhostIndices extends AbstractAction
{
    /**
     * Authorization level of a basic admin session.
     */
    public const ADMIN_RESOURCE = 'Smile_ElasticsuiteIndices::remove';

    /**
     * @var GhostIndexPurger
     */
    private GhostIndexPurger $ghostIndexPurger;

    /**
     * Constructor.
     *
     * @param Context          $context          The current context.
     * @param GhostIndexPurger $ghostIndexPurger Ghost index purging service.
     */
    public function __construct(
        Context $context,
        GhostIndexPurger $ghostIndexPurger
    ) {
        parent::__construct($context);
        $this->ghostIndexPurger = $ghostIndexPurger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @throws Exception
     */
    public function execute()
    {
        if (!$this->_isAllowed()) {
            $this->messageManager->addErrorMessage(__('Access denied.'));

            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        $deletedCount = $this->ghostIndexPurger->purge();

        if ($deletedCount > 0) {
            $this->messageManager->addSuccessMessage(__('%1 ghost indices were deleted.', $deletedCount));
        } else {
            $this->messageManager->addNoticeMessage(__('No ghost indices were deleted.'));
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
