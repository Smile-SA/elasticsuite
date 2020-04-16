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
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Smile\ElasticsuiteThesaurus\Api\Data\ThesaurusInterface;

/**
 * Thesaurus import
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
     * If we should check column names
     * @var boolean
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     * @var boolean
     */
    protected $logInHistory = true;

    /**
     * Import Provider
     * @var Provider
     */
    protected $importProvider;

    /**
     * Valid column names
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
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Row validation
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
     * Import data
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function _importData(): bool
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->deleteThesaurus();
                break;
            case Import::BEHAVIOR_REPLACE:
            case Import::BEHAVIOR_APPEND:
                $this->saveAndReplaceThesaurus();
                break;
        }

        return true;
    }

    /**
     * Init Error Messages
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
            __('The type must be ' . ThesaurusInterface::TYPE_SYNONYM . 'or' . ThesaurusInterface::TYPE_EXPANSION . '.')
        );
        $this->addMessageTemplate(
            'termsIsRequired',
            __('The terms cannot be empty.')
        );
        $this->addMessageTemplate(
            'statusMustBeZeroOrOne',
            __('The status must be zero or one.')
        );
    }

    /**
     * Delete thesaurus
     *
     * @return bool
     */
    private function deleteThesaurus(): bool
    {
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->validateRow($rowData, $rowNum);

                if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    $rowId = $rowData[ThesaurusInterface::THESAURUS_ID];
                    $rows[] = $rowId;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                }
            }
        }

        if ($rows) {
            return $this->deleteThesaurusFinish(array_unique($rows));
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * Save and replace thesaurus
     *
     * @return void
     */
    private function saveAndReplaceThesaurus(): void
    {
        $behavior = $this->getBehavior();
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $thesaurusList = [];

            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);

                    continue;
                }

                $rowId = $row[ThesaurusInterface::THESAURUS_ID];
                $rows[] = $rowId;
                $columnValues = [];

                foreach ($this->getAvailableColumns() as $columnKey) {
                    if ($columnKey === self::COL_STORES) {
                        $row[$columnKey] = $this->importProvider->processStoresData($row[$columnKey]);
                    }
                    if ($columnKey === self::COL_TERMS) {
                        $type = $columnValues[ThesaurusInterface::TYPE];
                        $row[$columnKey] = $this->importProvider->processTermsData($row[$columnKey], $type);
                    }
                    $columnValues[$columnKey] = $row[$columnKey];
                }

                $thesaurusList[$rowId][] = $columnValues;
            }

            if ($thesaurusList) {
                if (Import::BEHAVIOR_REPLACE === $behavior) {
                    if ($rows && $this->deleteThesaurusFinish(array_unique($rows))) {
                        $this->saveThesaurusFinish($thesaurusList);
                    }
                } elseif (Import::BEHAVIOR_APPEND === $behavior) {
                    $this->saveThesaurusFinish($thesaurusList);
                }
            }
        }
    }

    /**
     * Save thesaurus
     *
     * @param array $thesaurusData Data.
     *
     * @return bool
     */
    private function saveThesaurusFinish(array $thesaurusData): bool
    {
        foreach ($thesaurusData as $thesaurusRows) {
            foreach ($thesaurusRows as $row) {
                $model = $this->importProvider->createThesaurus();
                if (Import::BEHAVIOR_REPLACE === $this->getBehavior()) {
                    unset($row[ThesaurusInterface::THESAURUS_ID]);
                }
                if (isset($row[ThesaurusInterface::THESAURUS_ID])) {
                    $model->load($row[ThesaurusInterface::THESAURUS_ID]);
                    if (!$model->getThesaurusId()) {
                        continue;
                    }
                    $this->countItemsUpdated++;
                }

                $model->setData($row);
                $storeIds = $row[self::COL_STORES];

                if ($storeIds) {
                    $model->setStoreIds($storeIds);
                }

                try {
                    $this->importProvider->saveThesaurus($model);

                    if (!isset($row[ThesaurusInterface::THESAURUS_ID])) {
                        $this->countItemsCreated++;
                    }
                } catch (Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Delete thesaurus
     *
     * @param array $thesaurusIds Thesaurus Ids.
     *
     * @return bool
     */
    private function deleteThesaurusFinish(array $thesaurusIds): bool
    {
        if ($thesaurusIds) {
            try {
                foreach ($thesaurusIds as $thesaurusId) {
                    $model = $this->importProvider->createThesaurus();
                    $model->load($thesaurusId);
                    if (!$model->getThesaurusId()) {
                        continue;
                    }
                    $this->countItemsDeleted++;
                    $this->importProvider->removeThesaurus($model);
                }

                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }
}
