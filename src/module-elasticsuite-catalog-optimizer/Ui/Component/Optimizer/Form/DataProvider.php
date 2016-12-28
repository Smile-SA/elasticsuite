<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalogOptimizer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalogOptimizer\Ui\Component\Optimizer\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
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
     * @var array
     */
    private $loadedData;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OptimizerRepositoryInterface
     */
    private $optimizerRepository;

    /**
     * DataProvider constructor
     *
     * @param string                       $name                       Component Name
     * @param string                       $primaryFieldName           Primary Field Name
     * @param string                       $requestFieldName           Request Field Name
     * @param OptimizerCollectionFactory   $optimizerCollectionFactory Optimizer Collection Factory
     * @param Registry                     $registry                   The Registry
     * @param RequestInterface             $request                    The Request
     * @param OptimizerRepositoryInterface $optimizerRepository        The Optimizer Repository
     * @param array                        $meta                       Component Metadata
     * @param array                        $data                       Component Data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        OptimizerCollectionFactory $optimizerCollectionFactory,
        Registry $registry,
        RequestInterface $request,
        OptimizerRepositoryInterface $optimizerRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->collection          = $optimizerCollectionFactory->create();
        $this->registry            = $registry;
        $this->request             = $request;
        $this->optimizerRepository = $optimizerRepository;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get Component data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $optimizer = $this->getCurrentOptimizer();

        if ($optimizer) {
            $optimizerData = $optimizer->getData();
            if (!empty($optimizerData)) {
                $this->loadedData[$optimizer->getId()] = $optimizerData;
            }
        }

        return $this->loadedData;
    }

    /**
     * Get current optimizer
     *
     * @return Optimizer
     * @throws NoSuchEntityException
     */
    private function getCurrentOptimizer()
    {
        $optimizer = $this->registry->registry('current_optimizer');

        if ($optimizer) {
            return $optimizer;
        }

        $requestId = $this->request->getParam($this->requestFieldName);
        if ($requestId) {
            $optimizer = $this->optimizerRepository->getById($requestId);
        }

        if (!$optimizer || !$optimizer->getId()) {
            $optimizer = $this->collection->getNewEmptyItem();
        }

        return $optimizer;
    }
}
