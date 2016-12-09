<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection as OptimizerCollection;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer as ResourceOptimizer;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Optimizer Repository Object
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class OptimizerRepository implements OptimizerRepositoryInterface
{
    /**
     * @var ResourceOptimizer
     */
    protected $resource;

    /**
     * Optimizer Factory
     *
     * @var OptimizerFactory
     */
    private $optimizerFactory;

    /**
     * repository cache for optimizer, by ids
     *
     * @var \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface[]
     */
    private $optimizerRepositoryById = [];

    /**
     * Optimizer Collection Factory
     *
     * @var OptimizerCollection
     */
    private $optimizerCollectionFactory;

    /**
     * PHP Constructor
     *
     * @param OptimizerFactory    $optimizerFactory           Optimizer Factory.
     * @param ResourceOptimizer   $resource                   Resource optimizer.
     * @param OptimizerCollection $optimizerCollectionFactory Optimizer Collection Factory.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        OptimizerFactory $optimizerFactory,
        ResourceOptimizer $resource,
        OptimizerCollection $optimizerCollectionFactory
    ) {
        $this->optimizerFactory           = $optimizerFactory;
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
        $this->resource                   = $resource;
    }

    /**
     * Retrieve a optimizer by its ID
     *
     * @param int $optimizerId Id of the optimizer.
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($optimizerId)
    {
        if (!isset($this->optimizerRepositoryById[$optimizerId])) {
            /** @var OptimizerInterface $optimizer */
            $optimizer = $this->optimizerFactory->create()->load($optimizerId);
            if (!$optimizer->getOptimizerId()) {
                $exception = new NoSuchEntityException();
                throw $exception->singleField('optimizerId', $optimizerId);
            }

            $this->optimizerRepositoryById[$optimizerId] = $optimizer;
        }

        return $this->optimizerRepositoryById[$optimizerId];
    }

    /**
     * Retrieve list of optimizer
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerSearchResultsInterface
     */
    public function getList()
    {
        $collection = $this->optimizerCollectionFactory->create();
        $optimizers = $collection->getItems();

        return $optimizers;
    }

    /**
     * save a optimizer
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer Optimizer
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer)
    {
        try {
            $this->resource->save($optimizer);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the optimizer: %1',
                $exception->getMessage()
            ));
        }

        $this->optimizerRepositoryById[$optimizer->getOptimizerId()] = $optimizer;

        return $optimizer;
    }

    /**
     * delete a optimizer
     *
     * @param \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer Optimizer
     *
     * @return \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function delete(\Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer)
    {
        $optimizerId = $optimizer->getOptimizerId();

        $this->resource->delete($optimizer);

        if (isset($this->optimizerRepositoryById[$optimizerId])) {
            unset($this->optimizerRepositoryById[$optimizerId]);
        }

        return $optimizer;
    }

    /**
     * Remove optimizer by given ID
     *
     * @param int $optimizerId Id of the optimizer.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function deleteById($optimizerId)
    {
        return $this->delete($this->getById($optimizerId));
    }
}
