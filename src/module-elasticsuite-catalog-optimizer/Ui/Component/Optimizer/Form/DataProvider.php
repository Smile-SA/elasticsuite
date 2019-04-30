<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Registry;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\Optimizer;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\CollectionFactory as OptimizerCollectionFactory;

/**
 * Optimizer Data provider for adminhtml edit form
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var PoolInterface
     */
    private $modifierPool;

    /**
     * DataProvider constructor
     *
     * @param string                     $name                       Component Name
     * @param string                     $primaryFieldName           Primary Field Name
     * @param string                     $requestFieldName           Request Field Name
     * @param OptimizerCollectionFactory $optimizerCollectionFactory Optimizer Collection Factory
     * @param PoolInterface              $modifierPool               Modifiers Pool
     * @param array                      $meta                       Component Metadata
     * @param array                      $data                       Component Data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        OptimizerCollectionFactory $optimizerCollectionFactory,
        PoolInterface $modifierPool,
        array $meta = [],
        array $data = []
    ) {
        $this->collection   = $optimizerCollectionFactory->create();
        $this->modifierPool = $modifierPool;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        foreach ($this->getCollection()->getItems() as $itemId => $item) {
            // Ensure optimizer config and rule get properly instantiated.
            $item->getResource()->afterLoad($item);
            $this->data[$itemId] = $item->toArray();
        }

        /** @var \Magento\Ui\DataProvider\Modifier\ModifierInterface $modifier */
        foreach ($this->modifierPool->getModifiersInstances() as $modifier) {
            $this->data = $modifier->modifyData($this->data);
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        $this->meta = parent::getMeta();

        /** @var \Magento\Ui\DataProvider\Modifier\ModifierInterface $modifier */
        foreach ($this->modifierPool->getModifiersInstances() as $modifier) {
            $this->meta = $modifier->modifyMeta($this->meta);
        }

        return $this->meta;
    }
}
