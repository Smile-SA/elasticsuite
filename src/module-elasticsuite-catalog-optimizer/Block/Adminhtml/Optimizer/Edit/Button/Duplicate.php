<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Block\Adminhtml\Optimizer\Edit\Button;

/**
 * Button for optimizer duplicate
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Duplicate extends AbstractButton
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->getOptimizer() && $this->getOptimizer()->getId()) {
            $data = [
                'label' => __('Duplicate'),
                'class' => 'duplicate',
                'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/duplicate', ['id' => $this->getOptimizer()->getId()])),
                'sort_order' => 20,
            ];
        }

        return $data;
    }
}
