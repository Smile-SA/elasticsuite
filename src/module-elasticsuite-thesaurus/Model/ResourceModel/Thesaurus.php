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
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model\ResourceModel;

use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus Resource Model
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Romain Ruaud <romain.ruaud@smile.fr>
 */
class Thesaurus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Retrieve Store Ids for a given thesaurus
     *
     * @param int $thesaurusId The thesaurus Id
     *
     * @return array
     */
    public function getStoreIdsFromThesaurusId($thesaurusId)
    {
        $connection = $this->getConnection();

        $select = $connection->select();

        $select->from($this->getTable(ThesaurusInterface::STORE_TABLE_NAME), ThesaurusInterface::STORE_ID)
            ->where(ThesaurusInterface::THESAURUS_ID . ' = ?', (int) $thesaurusId);

        return $connection->fetchCol($select);
    }

    /**
     * Retrieve Store Ids for a given thesaurus
     *
     * @param \Magento\Framework\Model\AbstractModel $object The thesaurus
     *
     * @return array
     */
    public function getTermsDataFromThesaurus($object)
    {
        $connection = $this->getConnection();
        $binds      = [':thesaurus_id' => (int) $object->getThesaurusId()];

        $select = $connection->select();

        $select->from(['expansion_table' => $this->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME)])
            ->where('expansion_table.' . ThesaurusInterface::THESAURUS_ID . ' = :thesaurus_id')
            ->group('term_id')
            ->columns(
                [
                    ThesaurusInterface::THESAURUS_ID => ThesaurusInterface::THESAURUS_ID,
                    'term_id' => 'term_id',
                    'values' => new \Zend_Db_Expr("GROUP_CONCAT( expansion_table.term SEPARATOR ',')"),
                ]
            );

        // Retrieve also reference term if needed.
        if ($object->getType() === ThesaurusInterface::TYPE_EXPANSION) {
            $select->joinLeft(
                ['ref' => $this->getTable(ThesaurusInterface::REFERENCE_TABLE_NAME)],
                new \Zend_Db_Expr("ref.term_id = expansion_table.term_id AND ref.thesaurus_id = expansion_table.thesaurus_id"),
                ['reference_term' => 'term']
            );
        }

        $termsData = $connection->fetchAll($select, $binds);

        return $termsData;
    }

    /**
     * Internal Constructor
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(ThesaurusInterface::TABLE_NAME, ThesaurusInterface::THESAURUS_ID);
    }

    /**
     * Saves thesaurus linking to terms and stores after save
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Framework\Model\AbstractModel $object Thesaurus to save
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);

        $this->saveStoreRelation($object);
        $this->saveTermsRelation($object);

        return $this;
    }

    /**
     * Perform operations after object load, restore linking with terms and stores
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @param \Magento\Framework\Model\AbstractModel $object Thesaurus being loaded
     *
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $stores = $this->getStoreIdsFromThesaurusId($object->getId());
            $object->setStoreIds($stores);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Saves relation between thesaurus and store
     *
     * @param \Magento\Framework\Model\AbstractModel $object Thesaurus to save
     *
     * @return void
     */
    private function saveStoreRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        $storeIds = $object->getStoreIds();

        if (is_array($storeIds) && (count($storeIds) > 0)) {
            if (in_array(0, $storeIds)) {
                $storeIds = [0];
            }

            $storeLinks = [];
            $deleteCondition = [ThesaurusInterface::THESAURUS_ID . " = ?" => $object->getThesaurusId()];

            foreach ($storeIds as $storeId) {
                $storeLinks[] = [
                    ThesaurusInterface::THESAURUS_ID  => (int) $object->getThesaurusId(),
                    ThesaurusInterface::STORE_ID      => (int) $storeId,
                ];
            }

            $deleteCondition[ThesaurusInterface::STORE_ID . " NOT IN (?)"] = $storeIds;

            $this->getConnection()->delete($this->getTable(ThesaurusInterface::STORE_TABLE_NAME), $deleteCondition);
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(ThesaurusInterface::STORE_TABLE_NAME),
                $storeLinks,
                array_keys(current($storeLinks))
            );
        }
    }

    /**
     * Saves relation between thesaurus and store
     *
     * @param \Magento\Framework\Model\AbstractModel $object Thesaurus to save
     *
     * @return void
     */
    private function saveTermsRelation(\Magento\Framework\Model\AbstractModel $object)
    {
        $termRelations = $object->getTermsRelations() ?? [];
        $termRelations = array_filter($termRelations);

        if (count($termRelations) > 0) {
            $expansionTermLinks = [];
            $referenceTermLinks = [];

            $termId = 0;
            foreach ($termRelations as $termData) {
                if (empty($termData['values'])) {
                    continue;
                }

                $termId++;

                if ($object->getType() === ThesaurusInterface::TYPE_EXPANSION) {
                    $referenceTermLinks[] = [
                        'term_id'                        => $termId,
                        'term'                           => trim(strtolower($termData['reference_term'])),
                        ThesaurusInterface::THESAURUS_ID => (int) $object->getThesaurusId(),
                    ];
                }

                $termList = explode(",", $termData['values']);
                foreach ($termList as $term) {
                    $expansionTermLinks[] = [
                        'term_id'                        => $termId,
                        'term'                           => trim(strtolower($term)),
                        ThesaurusInterface::THESAURUS_ID => (int) $object->getThesaurusId(),
                    ];
                }
            }

            $this->deleteThesaurusRelations($object);

            // Saves expansion terms for a thesaurus. Expansion terms are used by expansion AND synonym thesauri.
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME),
                $expansionTermLinks,
                array_keys(current($expansionTermLinks))
            );

            $this->saveReferenceTerms($object, $referenceTermLinks);
        }
    }

    /**
     * Delete thesaurus previous relations.
     *
     * @param \Magento\Framework\Model\AbstractModel $object Thesaurus to save
     *
     * @return $this
     */
    private function deleteThesaurusRelations(\Magento\Framework\Model\AbstractModel $object)
    {
        $deleteCondition = [ThesaurusInterface::THESAURUS_ID . " = ?" => $object->getThesaurusId()];

        $this->getConnection()->delete(
            $this->getTable(ThesaurusInterface::EXPANSION_TABLE_NAME),
            $deleteCondition
        );

        return $this;
    }

    /**
     * Saves reference terms for a thesaurus. Reference term are used by expansions thesaurus only.
     *
     * @param \Magento\Framework\Model\AbstractModel $object         Thesaurus to save
     * @param array                                  $referenceTerms Thesaurus reference terms
     *
     * @return $this
     */
    private function saveReferenceTerms(\Magento\Framework\Model\AbstractModel $object, $referenceTerms)
    {
        if ($object->getType() === ThesaurusInterface::TYPE_EXPANSION) {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable(ThesaurusInterface::REFERENCE_TABLE_NAME),
                $referenceTerms,
                array_keys(current($referenceTerms))
            );
        }

        return $this;
    }
}
