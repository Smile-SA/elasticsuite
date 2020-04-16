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
namespace Smile\ElasticsuiteIndices\Block\Widget\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Action as ActionBlock;
use Magento\Framework\DataObject;
use Smile\ElasticsuiteIndices\Controller\Adminhtml\Index\Delete;

/**
 * Widget Grid Column Renderer: Action
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Action extends ActionBlock
{
    /**
     * Renders column
     *
     * @param DataObject $row Data Object.
     * @return string
     */
    public function render(DataObject $row): string
    {
        $actions        = $this->getColumn()->getActions();
        $updatedActions = $actions;

        if ($row->getData('index_status') !== IndexStatus::GHOST_STATUS
            || !$this->_authorization->isAllowed(Delete::ADMIN_RESOURCE)) {
            if (isset($updatedActions['delete'])) {
                unset($updatedActions['delete']);
            }
        }

        // Do the render with the updated actions.
        $this->getColumn()->setActions($updatedActions);
        $out = parent::render($row);
        // Reset to default action for the rendering of next row.
        $this->getColumn()->setActions($actions);

        return $out;
    }
}
