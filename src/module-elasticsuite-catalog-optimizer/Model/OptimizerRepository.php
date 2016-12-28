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

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\ResourceModel\Optimizer\Collection as OptimizerCollection;
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
     * @var \Magento\Framework\EntityManager\EntityManager
     */
    private $entityManager;

    /**
     * PHP Constructor
     *
     * @param OptimizerFactory    $optimizerFactory           Optimizer Factory.
     * @param OptimizerCollection $optimizerCollectionFactory Optimizer Collection Factory.
     * @param EntityManager       $entityManager              Entity Manager.
     */
    public function __construct(
        OptimizerFactory $optimizerFactory,
        OptimizerCollection $optimizerCollectionFactory,
        EntityManager $entityManager
    ) {
        $this->optimizerFactory           = $optimizerFactory;
        $this->optimizerCollectionFactory = $optimizerCollectionFactory;
        $this->entityManager              = $entityManager;
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
            $optimizerModel = $this->optimizerFactory->create();
            $optimizer = $this->entityManager->load($optimizerModel, $optimizerId);
            if (!$optimizer->getId()) {
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
            $this->entityManager->save($optimizer);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the optimizer: %1',
                $exception->getMessage()
            ));
        }

        $this->optimizerRepositoryById[$optimizer->getId()] = $optimizer;

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
        $optimizerId = $optimizer->getId();

        $this->entityManager->delete($optimizer);

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
