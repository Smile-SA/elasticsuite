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
use Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer\IndexStatus;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\AbstractAction;
use Smile\ElasticsuiteIndices\Model\IndexStatsProvider;
use Smile\ElasticsuiteIndices\Model\IndexStatusProvider;

/**
 * Controller for removing all ghost indices.
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
     * @var IndexStatsProvider
     */
    private IndexStatsProvider $indexStatsProvider;

    /**
     * @var IndexStatusProvider
     */
    private IndexStatusProvider $indexStatusProvider;

    /**
     * Constructor.
     *
     * @param Context             $context             The current context.
     * @param IndexStatsProvider  $indexStatsProvider  Index stats provider.
     * @param IndexStatusProvider $indexStatusProvider Index status provider.
     */
    public function __construct(
        Context $context,
        IndexStatsProvider $indexStatsProvider,
        IndexStatusProvider $indexStatusProvider
    ) {
        parent::__construct($context);
        $this->indexStatsProvider = $indexStatsProvider;
        $this->indexStatusProvider = $indexStatusProvider;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function execute()
    {
        if (!$this->_isAllowed()) {
            $this->messageManager->addErrorMessage(__('Access denied.'));

            return $this->_redirect($this->_redirect->getRefererUrl());
        }

        $deleted = [];

        $indices = $this->indexStatsProvider->getElasticSuiteIndices();

        foreach ($indices as $indexName => $alias) {
            if ($this->indexCanBeRemoved($indexName, $alias)) {
                try {
                    $this->indexStatsProvider->deleteIndex($indexName);
                    $deleted[] = $indexName;
                } catch (Exception $e) {
                    // Optional: Log the exception if needed.
                }
            }
        }

        if (!empty($deleted)) {
            $count = count($deleted);
            $this->messageManager->addSuccessMessage(__('%1 ghost indices were deleted.', $count));
        } else {
            $this->messageManager->addNoticeMessage(__('No ghost indices were deleted.'));
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * Determines if the index can be safely removed (is ghost).
     *
     * @param string      $indexName Index name.
     * @param string|null $alias     Index alias.
     * @return bool
     */
    private function indexCanBeRemoved(string $indexName, ?string $alias): bool
    {
        return $this->_isAllowed()
            && $this->indexStatusProvider->getIndexStatus($indexName, $alias) === IndexStatus::GHOST_STATUS;
    }
}
