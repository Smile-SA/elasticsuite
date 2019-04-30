<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Romain Ruaud <romain.ruaud@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Api\Data;

/**
 * Thesaurus Interface
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
interface ThesaurusInterface
{
    /**
     * Name of the main Mysql Table
     */
    const TABLE_NAME = 'smile_elasticsuite_thesaurus';

    /**
     * Constant for field thesaurus_id
     */
    const THESAURUS_ID = 'thesaurus_id';

    /**
     * Constant for field store_id
     */
    const STORE_ID = 'store_id';

    /**
     * Constant for field name
     */
    const NAME = 'name';

    /**
     * Constant for field name
     */
    const TYPE = 'type';

    /**
     * Constant for field is_active
     */
    const IS_ACTIVE = 'is_active';

    /**
     * Name of the link thesaurus/expansion terms TABLE
     */
    const STORE_TABLE_NAME = 'smile_elasticsuite_thesaurus_store';

    /**
     * Name of the expanded terms table, which also contains synonyms.
     */
    const EXPANSION_TABLE_NAME = 'smile_elasticsuite_thesaurus_expanded_terms';

    /**
     * Name of the reference terms table
     */
    const REFERENCE_TABLE_NAME = 'smile_elasticsuite_thesaurus_reference_terms';

    /**
     * Type of the Synonym Thesaurus
     */
    const TYPE_SYNONYM = 'synonym';

    /**
     * Type of the Expansion Thesaurus
     */
    const TYPE_EXPANSION = 'expansion';

    /**
     * Get Thesaurus ID
     *
     * @return int|null
     */
    public function getThesaurusId();

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Get store ids
     *
     * @return int[]
     */
    public function getStoreIds();

    /**
     * Get Thesaurus status
     *
     * @return bool
     */
    public function isActive();

    /**
     * Set Thesaurus ID
     *
     * @param int $identifier the value to save
     *
     * @return ThesaurusInterface
     */
    public function setThesaurusId($identifier);

    /**
     * Set name
     *
     * @param string $name the value to save
     *
     * @return ThesaurusInterface
     */
    public function setName($name);

    /**
     * Set type
     *
     * @param string $type the type of thesaurus to save
     *
     * @return ThesaurusInterface
     */
    public function setType($type);

    /**
     * Set store ids
     *
     * @param int[] $storeIds the store ids
     *
     * @return ThesaurusInterface
     */
    public function setStoreIds($storeIds);

    /**
     * Set Thesaurus status
     *
     * @param bool $status The thesaurus status
     *
     * @return ThesaurusInterface
     */
    public function setIsActive($status);
}
