<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile_ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Search\Request\SortOrder;

use Smile\ElasticSuiteCore\Search\Request\SortOrderInterface;

/**
 * Normal sort order implementation.
 *
 * @category Smile
 * @package  Smile_ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class Standard implements SortOrderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $direction;

    /**
     * Constructor.
     *
     * @param string $field     Sort order field.
     * @param string $direction Sort order direction.
     * @param string $name      Sort order name.
     */
    public function __construct($field, $direction, $name = null)
    {
        $this->name      = $name;
        $this->field     = $field;
        $this->direction = $direction;
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
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritDoc}
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return SortOrderInterface::TYPE_STANDARD;
    }
}
