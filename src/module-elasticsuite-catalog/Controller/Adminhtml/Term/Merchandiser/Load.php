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

/**
 * Search term merchandiser preview load controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Load extends \Magento\Search\Controller\Adminhtml\Term
{
    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * @var \Smile\ElasticsuiteCatalog\Model\Search\PreviewFactory
     */
    private $previewFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context                    $context        Controller context.
     * @param \Magento\Search\Model\QueryFactory                     $queryFactory   Search query factory.
     * @param \Magento\Framework\Json\Helper\Data                    $jsonHelper     Json Helper.
     * @param \Smile\ElasticsuiteCatalog\Model\Search\PreviewFactory $previewFactory Preview factory.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Smile\ElasticsuiteCatalog\Model\Search\PreviewFactory $previewFactory
    ) {
        parent::__construct($context);
        $this->queryFactory   = $queryFactory;
        $this->jsonHelper     = $jsonHelper;
        $this->previewFactory = $previewFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $queryId = $this->getRequest()->getParam('query_id', 0);
        $pageSize = $this->getRequest()->getParam('page_size');

        $query   = $this->queryFactory->create()->load($queryId);

        $responseData = ['products' => [], 'size' => 0];

        if ($query->getId()) {
            $productPositions = $this->getRequest()->getParam('product_position', []);
            $query->setSortedProductIds(array_keys($productPositions));
            $preview      = $this->previewFactory->create(['searchQuery' => $query, 'size' => $pageSize]);
            $responseData = $preview->getData();
        }

        $json = $this->jsonHelper->jsonEncode($responseData);
        $this->getResponse()->representJson($json);
    }
}
