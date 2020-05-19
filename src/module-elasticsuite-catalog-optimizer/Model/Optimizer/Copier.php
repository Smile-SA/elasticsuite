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
namespace Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterfaceFactory;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;

/**
 * Optimizer copier.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Copier
{
    /**
     * Optimizer Factory
     *
     * @var OptimizerInterfaceFactory
     */
    protected $optimizerFactory;

    /**
     * @var PoolInterface
     */
    private $modifierPool;

    /**
     * @param OptimizerInterfaceFactory $optimizerFactory Optimizer Factory.
     * @param PoolInterface             $modifierPool     Modifiers Pool.
     */
    public function __construct(
        OptimizerInterfaceFactory $optimizerFactory,
        PoolInterface $modifierPool
    ) {
        $this->optimizerFactory = $optimizerFactory;
        $this->modifierPool = $modifierPool;
    }

    /**
     * Create optimizer duplicate
     *
     * @param OptimizerInterface $optimizer Optimizer model.
     * @return OptimizerInterface
     */
    public function copy(OptimizerInterface $optimizer): OptimizerInterface
    {
        $optimizerData = [$optimizer->getId() => $optimizer->getData()];
        foreach ($this->modifierPool->getModifiersInstances() as $modifier) {
            $optimizerData = $modifier->modifyData($optimizerData);
        }
        $optimizerData = array_shift($optimizerData);
        /** @var Optimizer $duplicate */
        $duplicate = $this->optimizerFactory->create();
        $duplicate->setData($optimizerData);
        $duplicate->setFromDate(\DateTime::createFromFormat('Y-m-d', $optimizerData['from_date'])->format('m/d/Y'));
        $duplicate->setToDate(\DateTime::createFromFormat('Y-m-d', $optimizerData['to_date'])->format('m/d/Y'));
        $duplicate->setId(null);

        return $duplicate;
    }
}
