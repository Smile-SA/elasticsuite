<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticSuiteCore\Model\Search\Request\RelevanceConfig;

use Magento\Framework\App\RequestInterface;
use Smile\ElasticSuiteCore\Search\Request\ContainerConfiguration\BaseConfigInterface;

/**
 * Relevance configuration scope
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class ScopeDefiner extends \Magento\Config\Model\Config\ScopeDefiner
{
    /**
     * Request object
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request The request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Retrieve current config scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->request->getParam(
            'store'
        ) ? BaseConfigInterface::SCOPE_STORE_CONTAINERS : ($this->request->getParam(
            'container'
        ) ? BaseConfigInterface::SCOPE_CONTAINERS : BaseConfigInterface::SCOPE_DEFAULT);
    }
}
