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
        $actions = $this->getColumn()->getActions();

        if (empty($actions) || !is_array($actions) || $row->getData('index_status') !== IndexStatus::GHOST_STATUS) {
            return '&nbsp;';
        }

        foreach ($actions as $action) {
            if (is_array($action)) {
                return $this->_toLinkHtml($action, $row);
            }
        }
    }
}
