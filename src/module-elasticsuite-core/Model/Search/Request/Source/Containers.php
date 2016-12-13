<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Model\Search\Request\Source;

use Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig;

/**
 * Search request containers source model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Containers implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Smile\ElasticsuiteCore\Search\Request\ContainerConfiguration\BaseConfig
     */
    private $baseConfig;

    /**
     * Constructor.
     *
     * @param BaseConfig $baseConfig The base configuration
     */
    public function __construct(BaseConfig $baseConfig)
    {
        $this->baseConfig = $baseConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->getContainers() as $container) {
            $options[] = ['value' => $container['name'], 'label' => __($container['label'])];
        }

        return $options;
    }

    /**
     * Retrieve all containers
     *
     * @return array
     */
    public function getContainers()
    {
        return $this->baseConfig->get();
    }

    /**
     * Retrieve a container by its code
     *
     * @param string $code The container code
     *
     * @return array
     */
    public function get($code)
    {
        return $this->baseConfig->get($code, []);
    }
}
