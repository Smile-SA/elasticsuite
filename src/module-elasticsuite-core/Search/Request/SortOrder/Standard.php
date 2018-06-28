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
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\SortOrder;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Normal sort order implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
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
    public function __construct($field, $direction = self::SORT_ASC, $name = null)
    {
        $this->field     = $field;
        $this->direction = $direction;
        $this->name      = $name;
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
        return $this->direction ?? self::SORT_ASC;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return SortOrderInterface::TYPE_STANDARD;
    }
}
