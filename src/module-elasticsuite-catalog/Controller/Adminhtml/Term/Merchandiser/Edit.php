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
 * Search term merchandiser edit form controller.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Edit extends \Magento\Search\Controller\Adminhtml\Term
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context      Controller context.
     * @param \Magento\Framework\Registry         $coreRegistry Registry.
     * @param \Magento\Search\Model\QueryFactory  $queryFactory Search query factory.
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Search\Model\QueryFactory $queryFactory
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->queryFactory = $queryFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $queryId = $this->getRequest()->getParam('id');
        $model   = $this->queryFactory->create();
        $result  = null;

        if (!$queryId) {
            $this->messageManager->addErrorMessage(__('No search specified.'));
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('search/term/index');
        }

        $model->load($queryId);
        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('This search no longer exists.'));
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('search/term/index');
        }

        if ($result === null) {
            $this->coreRegistry->register('current_catalog_search', $model);
            $result = $this->createPage();
            $result->getConfig()->getTitle()->prepend(__('Search results for "%1"', $model->getQueryText()));
        }

        return $result;
    }
}
