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
namespace Smile\ElasticsuiteIndices\Block\Widget\Grid;

use Magento\Backend\Block\Widget\Grid\Container as MagentoContainer;

/**
 * Widget Grid Container
 *
 * @category Smile
 * @package  Smile\ElasticsuiteIndices
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Container extends MagentoContainer
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();

        $this->removeButton('add');
    }
}
