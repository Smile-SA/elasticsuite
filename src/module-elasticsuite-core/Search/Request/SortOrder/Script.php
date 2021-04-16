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
 * @copyright 2021 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Search\Request\SortOrder;

use Smile\ElasticsuiteCore\Search\Request\SortOrderInterface;

/**
 * Script sort order implementation.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Script implements SortOrderInterface
{
    /**
     * Constant for Script field
     */
    const SCRIPT_FIELD = '_script';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $field = self::SCRIPT_FIELD;

    /**
     * @var string
     */
    private $direction;

    /**
     * @var string
     */
    private $missing;

    /**
     * @var string
     */
    private $scriptType;

    /**
     * @var string
     */
    private $lang;

    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $params;

    /**
     * Constructor.
     *
     * @param string $scriptType Script type.
     * @param string $lang       Script lang.
     * @param string $source     Script source.
     * @param array  $params     Script params.
     * @param string $direction  Sort order direction.
     * @param string $name       Sort order name.
     * @param string $missing    How to treat missing values.
     */
    public function __construct(
        $scriptType,
        $lang,
        $source,
        $params = [],
        $direction = self::SORT_ASC,
        $name = null,
        $missing = null
    ) {
        $this->name       = $name;
        $this->missing    = $missing;
        $this->scriptType = $scriptType;
        $this->lang       = $lang;
        $this->source     = $source;
        $this->params     = $params;
        $this->direction  = $direction;

        if ($this->missing === null) {
            $this->missing = $direction == self::SORT_ASC ? self::MISSING_LAST : self::MISSING_FIRST;
        }
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
        return SortOrderInterface::TYPE_SCRIPT;
    }

    /**
     * {@inheritDoc}
     */
    public function getMissing()
    {
        return $this->missing;
    }

    /**
     * Get script source, lang and params
     *
     * @return array
     */
    public function getScript()
    {
        return [
            'lang'   => $this->lang,
            'source' => $this->source,
            'params' => $this->params,
        ];
    }

    /**
     * Get script type
     *
     * @return string
     */
    public function getScriptType()
    {
        return $this->scriptType;
    }
}
