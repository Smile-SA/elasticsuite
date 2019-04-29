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

/**
 * Relevance Configuration Factory
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Factory extends \Magento\Config\Model\Config\Factory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Class constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager The object manager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create new config object
     *
     * @param array $data The object data
     *
     * @return \Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create('Smile\ElasticsuiteCore\Model\Search\Request\RelevanceConfig', $data);
    }
}
