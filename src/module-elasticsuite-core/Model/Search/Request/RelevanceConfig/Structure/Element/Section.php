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
namespace Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element;

use Magento\Config\Model\Config\Structure\Element\Iterator;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Module\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Section\Visibility as SectionVisibility;

/**
 * Relevance configuration section model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Section extends \Magento\Config\Model\Config\Structure\Element\Section
{
    /**
     * @var \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Section\Visibility
     */
    private $visibility;

    /**
     * Section constructor.
     *
     * @param StoreManagerInterface  $storeManager     The store manager
     * @param Manager                $moduleManager    The module manager
     * @param Iterator               $childrenIterator The children iterator
     * @param AuthorizationInterface $authorization    The authorization manager
     * @param SectionVisibility      $visibility       The visibility manager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        Iterator $childrenIterator,
        AuthorizationInterface $authorization,
        SectionVisibility $visibility
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
        return $this->visibility->isVisible($this, $this->_scope);
    }
}
