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

use Magento\Config\Model\Config\BackendClone\Factory;
use Magento\Config\Model\Config\Structure\Element\Dependency\Mapper;
use Magento\Config\Model\Config\Structure\Element\Iterator\Field;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Relevance configuration group model
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Group extends \Magento\Config\Model\Config\Structure\Element\Group
{
    /** @var \Smile\ElasticSuiteCore\Model\Relevance\Config\Structure\Element\Visibility  */
    private $visibility;

    /**
     * Group constructor.
     *
     * @param StoreManagerInterface $storeManager      The store manager
     * @param Manager               $moduleManager     The module manager
     * @param Field                 $childrenIterator  The children iterator
     * @param Factory               $cloneModelFactory The clone model factory
     * @param Mapper                $dependencyMapper  The dependency mapper
     * @param Visibility            $visibility        The visibility manager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        Field $childrenIterator,
        Factory $cloneModelFactory,
        Mapper $dependencyMapper,
        Visibility $visibility
    ) {
        $this->visibility = $visibility;
        parent::__construct($storeManager, $moduleManager, $childrenIterator, $cloneModelFactory, $dependencyMapper);
    }

    /**
     * Check for field visibility
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->visibility->isVisible($this, $this->_scope);
    }
}
