<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Adminhtml\Term\Merchandiser;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Magento\Search\Controller\Adminhtml\Term;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteCatalog\Model\Search\PreviewFactory;
use Smile\ElasticsuiteCore\Api\Search\ContextInterface;

/**
 * Search term merchandiser preview load controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Load extends Term
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var PreviewFactory
     */
    private $previewFactory;

    /**
     * @var ContextInterface
     */
    private $searchContext;

    /**
     * Constructor.
     *
     * @param Context          $context        Controller context.
     * @param QueryFactory     $queryFactory   Search query factory.
     * @param Data             $jsonHelper     Json Helper.
     * @param PreviewFactory   $previewFactory Preview factory.
     * @param ContextInterface $searchContext  Search context.
     */
    public function __construct(
        Context          $context,
        QueryFactory     $queryFactory,
        Data             $jsonHelper,
        PreviewFactory   $previewFactory,
        ContextInterface $searchContext
    ) {
        parent::__construct($context);
        $this->queryFactory   = $queryFactory;
        $this->jsonHelper     = $jsonHelper;
        $this->previewFactory = $previewFactory;
        $this->searchContext = $searchContext;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $queryId = $this->getRequest()->getParam('query_id', 0);
        $pageSize = $this->getRequest()->getParam('page_size');
        $search = $this->getRequest()->getParam('search');

        $query   = $this->queryFactory->create()->load($queryId);

        $responseData = ['products' => [], 'size' => 0];

        $this->searchContext->setIsBlacklistingApplied(false);
        if ($query->getId()) {
            $this->searchContext->setCurrentSearchQuery($query);
            $productPositions = $this->getRequest()->getParam('product_position', []);

            $query->setSortedProductIds(array_keys($productPositions));

            $preview      = $this->previewFactory->create(['searchQuery' => $query, 'size' => $pageSize, 'search' => $search]);
            $responseData = $preview->getData();
        }

        $json = $this->jsonHelper->jsonEncode($responseData);

        return $this->getResponse()->representJson($json);
    }
}
