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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticsuiteCore\Api\Index;

use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface;
use Smile\ElasticsuiteCore\Api\Index\Mapping\FieldFilterInterface;

/**
 * Representation of a Elasticsearch type mapping.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
interface MappingInterface
{
    const DEFAULT_SEARCH_FIELD       = 'search';
    const DEFAULT_SPELLING_FIELD     = 'spelling';
    const DEFAULT_AUTOCOMPLETE_FIELD = 'autocomplete';
    const DEFAULT_REFERENCE_FIELD    = 'reference';
    const DEFAULT_EDGE_NGRAM_FIELD   = 'edge_ngram';

    /**
     * List of the properties of the mapping.
     *
     * @return array
     */
    public function getProperties();

    /**
     * List of the fields used to build the mapping.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface[]
     */
    public function getFields();

    /**
     * Return a field of the mapping by name.
     *
     * @param string $name Field name
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getField($name);

    /**
     * Return the mapping as an array you can put into ES through the mapping API.
     *
     * @return array
     */
    public function asArray();

    /**
     * Field use as unique id for the doc.
     *
     * @return \Smile\ElasticsuiteCore\Api\Index\Mapping\FieldInterface
     */
    public function getIdField();

    /**
     * Return array of indexed by mapping properties used in search and weight as values.
     *
     * @param string|NULL               $analyzer     Search analyzer.
     * @param string|NULL               $defaultField Default field added to the list of fields.
     *                                                All field weighted with 1 will be ignored if present.
     * @param integer|null              $boost        A multiplier applied to fields default weight.
     * @param FieldFilterInterface|null $fieldFilter  A filter applied to fields.
     *
     * @return float[]
     */
    public function getWeightedSearchProperties(
        ?string $analyzer = null,
        ?string $defaultField = null,
        ?int $boost = 1,
        ?FieldFilterInterface $fieldFilter = null
    );

    /**
     * Check if mapping contains knn fields.
     *
     * @return bool
     */
    public function hasKnnFields() : bool;
}
