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

namespace Smile\ElasticsuiteCore\Search\Request;

/**
 * Search sort order specification.
 *
 * @category Smile
 * @package  Smile\ElasticsuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface SortOrderInterface
{
    const SORT_ASC  = 'asc';
    const SORT_DESC = 'desc';

    const MISSING_FIRST  = '_first';
    const MISSING_LAST   = '_last';

    const TYPE_STANDARD = 'standardSortOrder';
    const TYPE_NESTED   = 'nestedSortOrder';

    const SCORE_MODE_MIN = 'min';
    const SCORE_MODE_MAX = 'max';
    const SCORE_MODE_SUM = 'sum';
    const SCORE_MODE_AVG = 'avg';
    const SCORE_MODE_MED = 'median';

    const DEFAULT_SORT_NAME     = 'relevance';
    const DEFAULT_SORT_FIELD     = '_score';
    const DEFAULT_SORT_DIRECTION = self::SORT_DESC;

    /**
     * Sort order name.
     *
     * @return string
     */
    public function getName();

    /**
     * Field used for sort.
     *
     * @return string
     */
    public function getField();

    /**
     * Sort order direction.
     *
     * @return string
     */
    public function getDirection();

    /**
     * Sort order type.
     *
     * @return string
     */
    public function getType();

    /**
     * Sort order 'missing' directive.
     *
     * @return string
     */
    public function getMissing();
}
