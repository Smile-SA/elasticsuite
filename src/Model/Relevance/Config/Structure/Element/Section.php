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
namespace Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element;

use Magento\Config\Model\Config\Structure\Element\Iterator;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Section\Visibility;

/**
 * Relevance configuration section model
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Section extends \Magento\Config\Model\Config\Structure\Element\Section
{
    /**
     * @var \Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Section\Visibility
     */
    private $visibility;

    /**
     * Section constructor.
     *
     * @param StoreManagerInterface  $storeManager     The store manager
     * @param Manager                $moduleManager    The module manager
     * @param Iterator               $childrenIterator The children iterator
     * @param AuthorizationInterface $authorization    The authorization manager
     * @param Visibility             $visibility       The visibility manager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        Iterator $childrenIterator,
        AuthorizationInterface $authorization,
        Visibility $visibility
    ) {
        parent::__construct($storeManager, $moduleManager, $childrenIterator, $authorization);
        $this->visibility = $visibility;
    }

    /**
     * Check element visibility
     *
     * @return mixed
     */
    public function isVisible()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("RORUA VISIBILITY :: " . get_class($this));
        return $this->visibility->isVisible($this, $this->_scope);
    }

    /**
     * Set element data
     *
     * @param array $data
     * @param string $scope
     * @return void
     */
    public function setData(array $data, $scope)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        //$logger->info(print_r($data, true));
        $logger->info(print_r($scope, true));
        $logger->info("DATA WERE SET !");
        //$e = new \Exception();
        //$logger->info($e->getTraceAsString());
        parent::setData($data, $scope);
    }
}
