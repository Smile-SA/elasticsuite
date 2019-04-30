<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig;

use Magento\Framework\App\RequestInterface;
use Smile\ElasticsuiteCore\Api\Search\Request\ContainerScopeInterface;

/**
 * Relevance configuration scope
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
        ) ? ContainerScopeInterface::SCOPE_STORE_CONTAINERS : ($this->request->getParam(
            'container'
        ) ? ContainerScopeInterface::SCOPE_CONTAINERS : ContainerScopeInterface::SCOPE_DEFAULT);
    }
}
