<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Smile ElasticSuite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteThesaurus
 * @author    Dmytro ANDROSHCHUK <dmand@smile.fr>
 * @copyright 2020 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteThesaurus\Model\Import;

use Exception;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus import.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteThesaurus
 * @author   Dmytro ANDROSHCHUK <dmand@smile.fr>
 */
class Thesaurus extends AbstractEntity
{
    const ENTITY_CODE = 'thesaurus';
    const COL_TERMS = 'terms_relations';
    const COL_STORES = 'stores';

    /**
     * If we should check column names.
     * @var boolean
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history.
     *
     * @var boolean
     */
    protected $logInHistory = true;

    /**
     * Import Provider.
     *
     * @var Provider
     */
    protected $importProvider;

    /**
     * Valid column names.
     *
     * @var array
     */
    protected $validColumnNames = [
        ThesaurusInterface::THESAURUS_ID,
        ThesaurusInterface::NAME,
        ThesaurusInterface::TYPE,
        self::COL_TERMS,
        self::COL_STORES,
        ThesaurusInterface::IS_ACTIVE,
    ];

    /**
     * Whether import encountered a blocking row-level error
     * that already explains why nothing was imported.
     *
     * @var boolean
     */
    private $hasRowLevelErrors = false;

    /**
     * Import constructor.
     *
     * @param JsonHelper                         $jsonHelper       Json Helper.
     * @param ImportHelper                       $importExportData Import Helper.
     * @param Data                               $importData       Import Data.
     * @param Helper                             $resourceHelper   Resource Helper.
     * @param ProcessingErrorAggregatorInterface $errorAggregator  Error Aggregator.
     * @param Provider                           $importProvider   Import Provider.
     */
    public function __construct(
        JsonHelper $jsonHelper,
        ImportHelper $importExportData,
        Data $importData,
        Helper $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        Provider $importProvider
    ) {
        $this->jsonHelper          = $jsonHelper;
        $this->_importExportData   = $importExportData;
        $this->_resourceHelper     = $resourceHelper;
        $this->_dataSourceModel    = $importData;
        $this->errorAggregator     = $errorAggregator;
        $this->importProvider      = $importProvider;

        $this->initMessageTemplates();
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return static::ENTITY_CODE;
    }

    /**
     * Get available columns.
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Row validation.
     *
     * @param array $rowData Data.
     * @param int   $rowNum  Row number.
     *
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        $name = $rowData[ThesaurusInterface::NAME] ?? '';
        $type = $rowData[ThesaurusInterface::TYPE] ?? '';
        $terms = $rowData[self::COL_TERMS] ?? '';
        $active = (int) $rowData[ThesaurusInterface::IS_ACTIVE];

        if (!$name) {
            $this->addRowError('nameIsRequired', $rowNum);
        }
        if (!$type) {
            $this->addRowError('typeIsRequired', $rowNum);
        }
        if (!in_array($type, [ThesaurusInterface::TYPE_SYNONYM, ThesaurusInterface::TYPE_EXPANSION])) {
            $this->addRowError('typeMustBeValid', $rowNum);
        }
        if (!$terms) {
            $this->addRowError('termsIsRequired', $rowNum);
        }
        if (!in_array($active, [0, 1])) {
            $this->addRowError('statusMustBeZeroOrOne', $rowNum);
        }

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Import data.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {
        $createdBefore = $this->countItemsCreated;
        $updatedBefore = $this->countItemsUpdated;
        $deletedBefore = $this->countItemsDeleted;

        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_APPEND:
                $this->processThesaurusUpsert();
                break;

            case Import::BEHAVIOR_REPLACE:
                $this->processThesaurusReplace();
                break;

            case Import::BEHAVIOR_DELETE:
                $this->processThesaurusDelete();
                break;
        }

        /**
         * Global "no-op import" warning.
         *
         * Show ONLY when:
         * - No create, update, or delete happened;
         * - AND there were NO blocking thesaurus_id row-level errors.
         *
         * This ensures:
         * - APPEND = global warning shown;
         * - REPLACE / DELETE with invalid IDs = row warning ONLY.
         */
        if ($this->countItemsCreated === $createdBefore &&
            $this->countItemsUpdated === $updatedBefore &&
            $this->countItemsDeleted === $deletedBefore &&
            !$this->hasRowLevelErrors
        ) {
            $this->getErrorAggregator()->addError(
                'noThesaurusImported',
                ProcessingError::ERROR_LEVEL_NOT_CRITICAL
            );
        }

        return true;
    }

    /**
     * APPEND behavior:
     *  - Create new thesauri (no ID);
     *  - Update existing thesauri (existing ID);
     *  - Existing thesauri data is never deleted.
     *
     * @return void
     */
    private function processThesaurusUpsert(): void
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $thesaurusData = $this->collectThesaurusRows($bunch);

            if (!$thesaurusData) {
                continue;
            }

            $this->persistThesaurusData($thesaurusData, false);
        }
    }

    /**
     * REPLACE behavior:
     *  - Delete existing thesauri referenced by valid thesaurus_id values;
     *  - Recreate them from CSV rows;
     *  - If no valid thesaurus exists, a row-level error is raised.
     *
     * @return void
     */
    private function processThesaurusReplace(): void
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $thesaurusData = $this->collectThesaurusRows($bunch);

            if (!$thesaurusData) {
                continue;
            }

            // Filter out rows for non-existent thesauri.
            $existingData = [];
            foreach ($thesaurusData as $thesaurusId => $rows) {
                $model = $this->importProvider->createThesaurus();
                $model->load($thesaurusId);

                if ($model->getThesaurusId()) {
                    $existingData[$thesaurusId] = $rows;
                }
            }

            // If NOTHING valid exists add row-level error explains no-op.
            if (!$existingData) {
                $this->hasRowLevelErrors = true;
                $this->getErrorAggregator()->addError('thesaurusDoesNotExist');
                continue;
            }

            // Delete existing thesauri that are actually present.
            $this->deleteExistingThesauri(array_keys($existingData));
            // Recreate them.
            $this->persistThesaurusData($existingData, true);
        }
    }

    /**
     * DELETE behavior:
     *  - Remove existing thesauri referenced by thesaurus_id;
     *  - Raise a row-level error if no valid thesaurus can be deleted.
     *
     * @return void
     */
    private function processThesaurusDelete(): void
    {
        $idsToDelete = [];

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if (!empty($rowData[ThesaurusInterface::THESAURUS_ID])) {
                    $idsToDelete[] = $rowData[ThesaurusInterface::THESAURUS_ID];
                }
            }
        }

        if (!$idsToDelete) {
            $this->hasRowLevelErrors = true;
            $this->getErrorAggregator()->addError('thesaurusDoesNotExist');

            return;
        }

        $this->deleteExistingThesauri(array_unique($idsToDelete));
    }

    /**
     * Collect, validate, normalize and group CSV rows by thesaurus_id.
     *
     * Null thesaurus_id means creation.
     *
     * @param array $bunch Raw CSV rows for the current import batch.
     *
     * @return array
     */
    private function collectThesaurusRows(array $bunch): array
    {
        $thesaurusData = [];

        foreach ($bunch as $rowNum => $row) {
            if (!$this->validateRow($row, $rowNum)) {
                continue;
            }

            $rowId = $row[ThesaurusInterface::THESAURUS_ID] ?? null;
            $thesaurusData[$rowId][] = $this->prepareRowData($row);
        }

        return $thesaurusData;
    }

    /**
     * Normalize CSV row values (stores, terms).
     *
     * @param array $row Raw CSV row data after validation.
     *
     * @return array
     */
    private function prepareRowData(array $row): array
    {
        foreach ($this->validColumnNames as $columnKey) {
            if ($columnKey === self::COL_STORES) {
                $row[$columnKey] = $this->importProvider->processStoresData($row[$columnKey]);
            }

            if ($columnKey === self::COL_TERMS) {
                $row[$columnKey] = $this->importProvider->processTermsData(
                    $row[$columnKey],
                    $row[ThesaurusInterface::TYPE]
                );
            }
        }

        return $row;
    }

    /**
     * Persist thesaurus data.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @param array $thesaurusData Thesaurus data.
     * @param bool  $forceCreate   Whether to ignore thesaurus_id and recreate.
     *
     * @return void
     */
    private function persistThesaurusData(array $thesaurusData, bool $forceCreate = false): void
    {
        foreach ($thesaurusData as $thesaurusRows) {
            foreach ($thesaurusRows as $row) {
                $model = $this->importProvider->createThesaurus();

                // In REPLACE mode, we only force-create rows for existing IDs.
                if (!$forceCreate && isset($row[ThesaurusInterface::THESAURUS_ID])) {
                    $model->load($row[ThesaurusInterface::THESAURUS_ID]);
                    if (!$model->getThesaurusId()) {
                        continue;
                    }
                    $this->countItemsUpdated++;
                } else {
                    unset($row[ThesaurusInterface::THESAURUS_ID]);
                    $this->countItemsCreated++;
                }

                $model->setData($row);

                if (!empty($row[self::COL_STORES])) {
                    $model->setStoreIds($row[self::COL_STORES]);
                }

                try {
                    $this->importProvider->saveThesaurus($model);
                } catch (Exception $e) {
                    // Magento import framework handles persistence errors.
                }
            }
        }
    }

    /**
     * Delete existing thesauri by IDs.
     *
     * Adds a row-level error if a referenced thesaurus does not exist.
     *
     * @param array $thesaurusIds List of thesaurus entity IDs to delete.
     *
     * @return void
     */
    private function deleteExistingThesauri(array $thesaurusIds): void
    {
        foreach ($thesaurusIds as $thesaurusId) {
            $model = $this->importProvider->createThesaurus();
            $model->load($thesaurusId);

            if (!$model->getThesaurusId()) {
                $this->hasRowLevelErrors = true;
                $this->getErrorAggregator()->addError('thesaurusDoesNotExist');
                continue;
            }

            try {
                $this->importProvider->removeThesaurus($model);
                $this->countItemsDeleted++;
            } catch (Exception $e) {
                // Deletion failures are intentionally ignored.
            }
        }
    }

    /**
     * Init Error Messages.
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'nameIsRequired',
            __('The name cannot be empty.')
        );
        $this->addMessageTemplate(
            'typeIsRequired',
            __('The type cannot be empty.')
        );
        $this->addMessageTemplate(
            'typeMustBeValid',
            __('The type must be ' . ThesaurusInterface::TYPE_SYNONYM . ' or ' . ThesaurusInterface::TYPE_EXPANSION . '.')
        );
        $this->addMessageTemplate(
            'termsIsRequired',
            __('The terms cannot be empty.')
        );
        $this->addMessageTemplate(
            'statusMustBeZeroOrOne',
            __('The status must be zero or one.')
        );
        $this->addMessageTemplate(
            'thesaurusDoesNotExist',
            __(
                'Thesaurus with provided ID does not exist. ' .
                'If your CSV file contains a "thesaurus_id" value, ensure that thesaurus with this ID exists.'
            )
        );
        $this->addMessageTemplate(
            'noThesaurusImported',
            __(
                'Import completed but no thesaurus was created or updated. ' .
                'If your CSV file contains a "thesaurus_id" value, ensure that thesaurus with this ID exists. ' .
                'To create a new thesaurus, leave the "thesaurus_id" column empty.'
            )
        );
    }
}
