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

use Magento\Config\Model\Config\BackendFactory;
use Magento\Config\Model\Config\CommentFactory;
use Magento\Config\Model\Config\SourceFactory;
use Magento\Config\Model\Config\Structure\Element\Dependency\Mapper;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\FieldInterface;

/**
 * Custom field element to manager container scope visibility
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Field extends \Magento\Config\Model\Config\Structure\Element\Field implements FieldInterface
{
    /** @var \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig\Structure\Element\Visibility  */
    private $visibility;

    /**
     * Field constructor.
     *
     * @param StoreManagerInterface $storeManager     The store Manager
     * @param Manager               $moduleManager    The module Manager
     * @param BackendFactory        $backendFactory   The backend model factory
     * @param SourceFactory         $sourceFactory    The source model factory
     * @param CommentFactory        $commentFactory   The comment factory
     * @param BlockFactory          $blockFactory     The block factory
     * @param Mapper                $dependencyMapper The dependency manager
     * @param Visibility            $visibility       The visibility manager
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Manager $moduleManager,
        BackendFactory $backendFactory,
        SourceFactory $sourceFactory,
        CommentFactory $commentFactory,
        BlockFactory $blockFactory,
        Mapper $dependencyMapper,
        Visibility $visibility
    ) {
        parent::__construct(
            $storeManager,
            $moduleManager,
            $backendFactory,
            $sourceFactory,
            $commentFactory,
            $blockFactory,
            $dependencyMapper
        );

        $this->visibility = $visibility;
    }

    /**
     * Check whether field should be shown in website scope
     *
     * @return bool
     */
    public function showInContainer()
    {
        return isset($this->_data['showInContainer']) && (int) $this->_data['showInContainer'];
    }

    /**
     * Check if the field is visible
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->visibility->isVisible($this, $this->_scope);
    }
}
