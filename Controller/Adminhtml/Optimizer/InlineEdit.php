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
namespace Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\Optimizer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;

use Smile\ElasticsuiteCatalogOptimizer\Api\OptimizerRepositoryInterface;
use Smile\ElasticsuiteCatalogOptimizer\Model\OptimizerFactory;
use Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface;
use Smile\ElasticsuiteCatalogOptimizer\Controller\Adminhtml\AbstractOptimizer;

/**
 * Optimizer Adminhtml Inline Editing controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalogOptimizer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class InlineEdit extends AbstractOptimizer
{
    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * @param Context                      $context             Application context
     * @param PageFactory                  $resultPageFactory   Result Page factory
     * @param Registry                     $coreRegistry        Application registry
     * @param OptimizerRepositoryInterface $optimizerRepository Optimizer Repository
     * @param OptimizerFactory             $optimizerFactory    Optimizer Factory
     * @param JsonFactory                  $jsonFactory         JSON Factory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $coreRegistry,
        OptimizerRepositoryInterface $optimizerRepository,
        OptimizerFactory $optimizerFactory,
        JsonFactory $jsonFactory
    ) {
        parent::__construct(
            $context,
            $resultPageFactory,
            $coreRegistry,
            $optimizerRepository,
            $optimizerFactory
        );

        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Process inline editing of optimizer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);

            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;

                return $resultJson->setData(['messages' => $messages, 'error' => $error]);
            }

            foreach (array_keys($postItems) as $optimizerId) {
                /** @var \Smile\ElasticsuiteCatalogOptimizer\Api\Data\OptimizerInterface $optimizer */
                $optimizer = $this->optimizerRepository->getById($optimizerId);
                try {
                    $optimizer->setData(array_merge($optimizer->getData(), $postItems[$optimizerId]));
                    $this->optimizerRepository->save($optimizer);
                } catch (\Exception $e) {
                    $messages[] = $this->getErrorWithOptimizerId($optimizer, __($e->getMessage()));
                    $error = true;
                }
            }
        }

        return $resultJson->setData(['messages' => $messages, 'error' => $error]);
    }

    /**
     * Add optimizer title to error message
     *
     * @param OptimizerInterface $optimizer The optimizer
     * @param string            $errorText The error message
     *
     * @return string
     */
    protected function getErrorWithOptimizerId(OptimizerInterface $optimizer, $errorText)
    {
        return '[Optimizer ID: ' . $optimizer->getId() . '] ' . $errorText;
    }
}
