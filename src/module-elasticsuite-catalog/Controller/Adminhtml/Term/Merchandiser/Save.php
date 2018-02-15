<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Adminhtml\Term\Merchandiser;

use Magento\Framework\Controller\ResultFactory;

/**
 * Search term merchandiser save controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Save extends \Magento\Search\Controller\Adminhtml\Term
{
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position
     */
    private $resourceModel;

    /**
     *
     * @param \Magento\Backend\App\Action\Context                                    $context       Context.
     * @param \Magento\Framework\Json\Helper\Data                                    $jsonHelper    JSON helper.
     * @param \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel Resource model.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteCatalog\Model\ResourceModel\Product\Search\Position $resourceModel
    ) {
        parent::__construct($context);
        $this->jsonHelper = $jsonHelper;
        $this->resourceModel = $resourceModel;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $queryId             = $this->getRequest()->getParam('query_id');
        $sortedProducts      = $this->getRequest()->getParam('sorted_products', []);
        $blacklistedProducts = $this->getRequest()->getParam('blacklisted_products', []);

        if (is_string($sortedProducts)) {
            try {
                $sortedProducts = $this->jsonHelper->jsonDecode($sortedProducts);
            } catch (\Exception $e) {
                $sortedProducts = [];
            }
        }

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('*/term/edit', ['id' => $queryId]);

        try {
            $this->resourceModel->saveProductPositions($queryId, $sortedProducts, $blacklistedProducts);

            if ($this->getRequest()->getParam('back') == "edit") {
                $result->setPath('*/*/edit', ['id' => $queryId]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Unable to save positions.'));
            $result->setPath('*/*/edit', ['id' => $queryId]);
        }

        return $result;
    }
}
