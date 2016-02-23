<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuite________
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Helper;
use Smile\ElasticSuiteCore\Api\Config\RequestContainerInterface;

/**
 * _________________________________________________
 *
 * @category Smile
 * @package  Smile_ElasticSuite______________
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class RelevanceConfiguration extends AbstractConfiguration
{
    protected $requestConfiguration;

    /**
     * Constructor.
     *
     * @param Context               $context      Helper context.
     * @param StoreManagerInterface $storeManager Store manager.
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager, RequestContainerInterface $requestConfiguration)
    {
        $this->storeManager = $storeManager;
        $this->requestConfiguration = $requestConfiguration;
        parent::__construct($context);
    }

    public function getContainersCode()
    {
        return array_keys($this->requestConfiguration->getContainers());
    }

    public function getContainers()
    {
        return $this->requestConfiguration->getContainers();
    }
}

