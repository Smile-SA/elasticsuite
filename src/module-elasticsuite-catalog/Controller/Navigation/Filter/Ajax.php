<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCatalog
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCatalog\Controller\Navigation\Filter;

/**
 * Navigation layer filters AJAX loading.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCatalog
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Ajax extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context           Controller action context.
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory JSON result factory.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        return $this->jsonResultFactory->create()->setData(new \stdClass());
    }
}
