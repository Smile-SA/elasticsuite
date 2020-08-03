<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCatalogOptimizer\Plugin\Adminhtml;

use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\OptimizerFilterInterface;

/**
 * Adminhtml only plugin, used to append the currently previewed optimizers (if any) to the list of allowed ones.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class OptimizerFilter
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * OptimizerFilter constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request The request
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Append currently edited optimizer into available list.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer\OptimizerFilterInterface $subject Optimizer Filter
     * @param                                                                              $result  Available Optimizers
     *
     * @return mixed
     */
    public function afterGetOptimizerIds(OptimizerFilterInterface $subject, $result)
    {
        $result[] = $this->getCurrentOptimizerId();

        return $result;
    }

    /**
     * Get current optimizer id
     *
     * @return string
     */
    private function getCurrentOptimizerId()
    {
        return $this->request->getPostValue('optimizer_id') ?? '';
    }
}
