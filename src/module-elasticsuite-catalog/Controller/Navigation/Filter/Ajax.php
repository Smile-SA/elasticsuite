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

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;

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
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * Constructor.
     *
     * @param Context     $context           Context.
     * @param JsonFactory $jsonResultFactory JSON result factory.
     */
    public function __construct(Context $context, JsonFactory $jsonResultFactory)
    {
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
