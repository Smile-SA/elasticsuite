<?php
/**
 * DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
* versions in the future.
*
* @category  Smile
* @package   Smile\ElasticsuiteCore
* @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2019 Smile
* @license   Open Software License ("OSL") v. 3.0
*/

namespace Smile\ElasticsuiteCore\Search\Request\Aggregation;

use Smile\ElasticsuiteCore\Search\Request\MetricInterface;

/**
 * ElasticSuite metric implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Metric extends \Magento\Framework\Search\Request\Aggregation\Metric implements MetricInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string $name   Metric name.
     * @param string $type   Metric type.
     * @param string $field  Metric field.
     * @param array  $config Metric extra config.
     */
    public function __construct($name, $type, $field, $config = [])
    {
        parent::__construct($type);
        $this->field = $field;
        $this->name  = $name;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return $this->config;
    }
}
